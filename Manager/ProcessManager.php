<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Manager;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Exception\CircularProcessException;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\InitializableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Execute processes
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessManager
{
    protected const EXECUTE_PROCESS = 1;
    protected const EXECUTE_PROCEED = 2;
    protected const EXECUTE_FLUSH = 4;

    /** @var ContainerInterface */
    protected $container;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ProcessConfigurationRegistry */
    protected $processConfigurationRegistry;

    /** @var TaskConfiguration */
    protected $blockingTaskConfiguration;

    /** @var ContextualOptionResolver */
    protected $contextualOptionResolver;

    /** @var TaskConfiguration[] */
    protected $processedIterables = [];

    /** @var TaskConfiguration[] */
    protected $processedBlockings = [];

    /**
     * @param ContainerInterface           $container
     * @param LoggerInterface              $logger
     * @param EntityManagerInterface       $entityManager
     * @param ProcessConfigurationRegistry $processConfigurationRegistry
     * @param ContextualOptionResolver     $contextualOptionResolver
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ProcessConfigurationRegistry $processConfigurationRegistry,
        ContextualOptionResolver $contextualOptionResolver
    ) {
        $this->container = $container;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->processConfigurationRegistry = $processConfigurationRegistry;
        $this->contextualOptionResolver = $contextualOptionResolver;
    }

    /**
     * @param string $processCode
     * @param mixed  $input
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function execute(string $processCode, $input = null, array $context = [])
    {
        $processConfiguration = $this->processConfigurationRegistry->getProcessConfiguration($processCode);
        $processHistory = $this->initializeStates($processConfiguration, $context);
        $this->checkProcess($processConfiguration);

        // First initialize the whole stack in a linear way, tasks are initialized in the order they are configured
        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->initialize($taskConfiguration);
        }

        // If defined, set the input of a task
        if ($processConfiguration->getEntryPoint()) {
            $processConfiguration->getEntryPoint()->getState()->setInput($input);
        }

        // Resolve task from main branch, starting by the end
        /** @var TaskConfiguration[] $taskList */
        $taskList = array_reverse($processConfiguration->getTaskConfigurations());
        $allowedTasks = $processConfiguration->getMainTaskGroup();
        foreach ($taskList as $taskConfiguration) {
            if (\in_array($taskConfiguration->getCode(), $allowedTasks, true)) {
                $this->resolve($taskConfiguration);
            }
        }

        // Finalize the process in a linear way
        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->finalize($taskConfiguration);
        }

        $this->endProcess($processHistory);

        // If defined, return the output of a task
        $returnValue = null;
        if ($processConfiguration->getEndPoint()) {
            $returnValue = $processConfiguration->getEndPoint()->getState()->getOutput();
        }

        return $returnValue;
    }

    /**
     * Resolve a task, by checking if parents are resolved and processing roots and BlockingTasks
     *
     * @TODO handle properly resolution of stopped task
     * @todo might be fixed?
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     *
     * @return bool
     */
    protected function resolve(TaskConfiguration $taskConfiguration): bool
    {
        $state = $taskConfiguration->getState();
        if ($state->isResolved()) {
            return true;
        }

        $state->setStatus(ProcessState::STATUS_PENDING);

        // Resolve parents first
        $allParentsResolved = true;
        foreach ($taskConfiguration->getPreviousTasksConfigurations() as $previousTasksConfiguration) {
            if (!$previousTasksConfiguration->getState()->isResolved()) {
                $isResolved = $this->resolve($previousTasksConfiguration);
                $allParentsResolved = $allParentsResolved && $isResolved;
            }
        }

        if (!$allParentsResolved) {
            throw new \UnexpectedValueException('Cannot resolve all parents');
        }

        $state->setStatus(ProcessState::STATUS_PROCESSING);

        // Start processing only roots that are not in error branch
        if ($taskConfiguration->isRoot()) {
            $this->process($taskConfiguration);
        }

        // Then we can proceed with BlockingTask only if they have been processed normally and where never "proceeded"
        if ($taskConfiguration->getTask() instanceof BlockingTaskInterface
            && $this->hasProcessedBlocking($taskConfiguration)
        ) {
            $this->process($taskConfiguration, self::EXECUTE_PROCEED);
        }

        $state->setStatus(ProcessState::STATUS_RESOLVED);

        return $state->isResolved();
    }

    /**
     * Fetch task service and run additional setup for InitializableTasks
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    protected function initialize(TaskConfiguration $taskConfiguration): void
    {
        // @todo Refactor this using a Registry with this feature:
        // https://symfony.com/doc/current/service_container/service_subscribers_locators.html
        $serviceReference = $taskConfiguration->getServiceReference();
        if (0 === strpos($serviceReference, '@')) {
            $task = $this->container->get(ltrim($serviceReference, '@'));
        } elseif ($this->container->has($serviceReference)) {
            $task = $this->container->get($serviceReference);
        } else {
            throw new \UnexpectedValueException(
                "Unable to resolve service reference for Task '{$taskConfiguration->getCode()}'"
            );
        }
        if (!$task instanceof TaskInterface) {
            throw new \UnexpectedValueException(
                "Service defined in Task '{$taskConfiguration->getCode()}' is not a TaskInterface"
            );
        }
        $taskConfiguration->setTask($task);

        if ($task instanceof InitializableTaskInterface) {
            $state = $taskConfiguration->getState();
            try {
                $task->initialize($state);
            } catch (\Throwable $e) {
                $logContext = $state->getLogContext();
                $logContext['exception'] = $e;
                $this->logger->critical($e->getMessage(), $logContext);
                $state->stop($e);
            }
        }
        $this->handleState($taskConfiguration->getState());
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param int               $executionFlag
     *
     * @throws \RuntimeException
     */
    protected function process(TaskConfiguration $taskConfiguration, int $executionFlag = self::EXECUTE_PROCESS): void
    {
        $state = $taskConfiguration->getState();
        do {
            // Execute the current task, and fetch status
            $this->processExecution($taskConfiguration, $executionFlag);

            $this->handleState($state);
            if ($state->isStopped()) {
                return;
            }

            // An error feed cannot be blocked or skipped (except if the process has been stopped)
            if ($state->hasError()) {
                foreach ($taskConfiguration->getErrorTasksConfigurations() as $errorTask) {
                    $this->prepareNextProcess($taskConfiguration, $errorTask, true);
                    $this->process($errorTask);
                    if ($state->isStopped()) {
                        return;
                    }
                }
            }

            // Run child items only if the state is not "skipped" and task is not blocking
            $task = $taskConfiguration->getTask();
            $shouldContinue =
                (!$task instanceof BlockingTaskInterface || self::EXECUTE_PROCEED === $executionFlag)
                && !$state->isSkipped();

            if ($shouldContinue) {
                foreach ($taskConfiguration->getNextTasksConfigurations() as $nextTaskConfiguration) {
                    $this->prepareNextProcess($taskConfiguration, $nextTaskConfiguration);
                    $this->process($nextTaskConfiguration);
                    if ($state->isStopped()) {
                        return;
                    }
                }
                $state->setSkipped(false); // Reset skipped state
            }

            $hasMoreItem = false; // By default, a task is not iterable
            if ($task instanceof IterableTaskInterface) {
                // Check if task has more items
                $hasMoreItem = $task->next($state);
                if (!$hasMoreItem) {
                    if (!$this->hasProcessedIterable($taskConfiguration)) {
                        return; // This means the task is empty
                    }
                    $this->flush($taskConfiguration);
                    // This means we are over iterating this task so we can remove it from registry
                    $this->removeProcessedIterable($taskConfiguration);
                    if ($state->isStopped()) {
                        return;
                    }
                    $this->proceed($taskConfiguration);
                }
                // Register the task has not empty
                $this->addProcessedIterable($taskConfiguration);
            }
        } while ($hasMoreItem);
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param int               $executionFlag
     */
    protected function processExecution(TaskConfiguration $taskConfiguration, int $executionFlag): void
    {
        $task = $taskConfiguration->getTask();
        $state = $taskConfiguration->getState();
        $state->setOutput(null);
        $state->setSkipped(false);
        try {
            if (self::EXECUTE_PROCESS === $executionFlag) {
                $this->logger->info(
                    "Processing task {$taskConfiguration->getCode()}",
                    [
                        'process_id' => $state->getProcessHistory()->getId(),
                        'process_code' => $state->getProcessHistory()->getProcessCode(),
                    ]
                );
                $task->execute($state);
                if ($task instanceof BlockingTaskInterface) {
                    $this->addProcessedBlocking($taskConfiguration);
                }
            } else {
                $state->setInput(null);
                $state->setPreviousState(null);
                if (self::EXECUTE_PROCEED === $executionFlag) {
                    if (!$task instanceof BlockingTaskInterface) {
                        // This exception should never be thrown
                        throw new \UnexpectedValueException(
                            "Task {$taskConfiguration->getCode()} is not blocking"
                        );
                    }
                    $this->logger->info(
                        "Proceeding task {$taskConfiguration->getCode()}",
                        [
                            'process_id' => $state->getProcessHistory()->getId(),
                            'process_code' => $state->getProcessHistory()->getProcessCode(),
                        ]
                    );
                    $task->proceed($state);
                    $this->removeProcessedBlocking($taskConfiguration);
                } elseif (self::EXECUTE_FLUSH === $executionFlag) {
                    if (!$task instanceof FlushableTaskInterface) {
                        // This exception should never be thrown
                        throw new \UnexpectedValueException(
                            "Task {$taskConfiguration->getCode()} is not flushable"
                        );
                    }
                    $this->logger->info(
                        "Flushing task {$taskConfiguration->getCode()}",
                        [
                            'process_id' => $state->getProcessHistory()->getId(),
                            'process_code' => $state->getProcessHistory()->getProcessCode(),
                        ]
                    );
                    $task->flush($state);
                } else {
                    throw new \UnexpectedValueException("Unknown execution flag: {$executionFlag}");
                }
            }
        } catch (\Throwable $e) {
            $state->setException($e);
            $state->setError($state->getInput());
            if ($taskConfiguration->getErrorStrategy() === TaskConfiguration::STRATEGY_SKIP) {
                $this->logger->critical($e->getMessage(), $state->getLogContext());
                $state->setSkipped(true);
            } elseif ($taskConfiguration->getErrorStrategy() === TaskConfiguration::STRATEGY_STOP) {
                $this->logger->critical($e->getMessage(), $state->getLogContext());
                $state->stop($e);
            }
        }
    }

    /**
     * Browse all children for FlushableTask until a BlockingTask is found
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \RuntimeException
     */
    protected function flush(TaskConfiguration $taskConfiguration): void
    {
        $task = $taskConfiguration->getTask();
        if ($task instanceof BlockingTaskInterface) {
            return;
        }
        if ($task instanceof FlushableTaskInterface) {
            $this->process($taskConfiguration, self::EXECUTE_FLUSH);
            if ($taskConfiguration->getState()->isStopped()) {
                return;
            }
        }
        // Check outputs
        foreach ($taskConfiguration->getNextTasksConfigurations() as $nextTaskConfiguration) {
            $this->flush($nextTaskConfiguration);
        }
        // Check errors
        foreach ($taskConfiguration->getErrorTasksConfigurations() as $errorTasksConfiguration) {
            $this->flush($errorTasksConfiguration);
        }
    }

    /**
     * Browse all children for BlockingTask and proceed with them
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \RuntimeException
     */
    protected function proceed(TaskConfiguration $taskConfiguration): void
    {
        $task = $taskConfiguration->getTask();
        if ($task instanceof BlockingTaskInterface) {
            $this->process($taskConfiguration, self::EXECUTE_PROCEED);
            if ($taskConfiguration->getState()->isStopped()) {
                return;
            }
        }
        // Check outputs
        foreach ($taskConfiguration->getNextTasksConfigurations() as $nextTaskConfiguration) {
            $this->proceed($nextTaskConfiguration);
        }
        // Check errors
        foreach ($taskConfiguration->getErrorTasksConfigurations() as $errorTasksConfiguration) {
            $this->proceed($errorTasksConfiguration);
        }
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \RuntimeException
     */
    protected function finalize(TaskConfiguration $taskConfiguration): void
    {
        $task = $taskConfiguration->getTask();
        if ($task instanceof FinalizableTaskInterface) {
            $state = $taskConfiguration->getState();
            try {
                $task->finalize($taskConfiguration->getState());
            } catch (\Throwable $e) {
                $logContext = $state->getLogContext();
                $logContext['exception'] = $e;
                $this->logger->critical($e->getMessage(), $logContext);
                $state->stop($e);
            }
            $this->handleState($state);
        }
    }

    /**
     * @param ProcessConfiguration $processConfiguration
     * @param array                $context
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     *
     * @return ProcessHistory
     */
    protected function initializeStates(
        ProcessConfiguration $processConfiguration,
        array $context = []
    ): ProcessHistory {
        $processHistory = new ProcessHistory($processConfiguration);

        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $state = new ProcessState($processConfiguration, $processHistory);
            $state->setContext($context);
            $state->setContextualOptionResolver($this->contextualOptionResolver);

            $taskConfiguration->setState($state);
            $state->setTaskConfiguration($taskConfiguration);
        }

        return $processHistory;
    }

    /**
     * @param TaskConfiguration $previousTaskConfiguration
     * @param TaskConfiguration $nextTaskConfiguration
     * @param bool              $isError
     */
    protected function prepareNextProcess(
        TaskConfiguration $previousTaskConfiguration,
        TaskConfiguration $nextTaskConfiguration,
        $isError = false
    ): void {
        if ($isError) {
            $input = $previousTaskConfiguration->getState()->getError();
        } else {
            $input = $previousTaskConfiguration->getState()->getOutput();
        }

        $nextState = $nextTaskConfiguration->getState();
        $nextState->setInput($input);
        $nextState->setPreviousState($previousTaskConfiguration->getState());
    }

    /**
     * Save the state of the import process
     *
     * @param ProcessState $state
     *
     * @throws \RuntimeException
     */
    protected function handleState(ProcessState $state): void
    {
        $processHistory = $state->getProcessHistory();
        if ($state->getException()) {
            $processHistory->setFailed();

            throw new \RuntimeException(
                "Process {$state->getProcessConfiguration()->getCode()} has failed",
                -1,
                $state->getException()
            );
        }
    }

    /**
     * @param ProcessHistory $history
     *
     */
    protected function endProcess(ProcessHistory $history): void
    {
        // Do not change state if already set
        if ($history->isStarted()) {
            $history->setSuccess();

            $this->logger->info(
                "Process {$history->getProcessCode()} succeed",
                [
                    'process_id' => $history->getId(),
                    'process_code' => $history->getProcessCode(),
                    'duration' => $history->getDuration(),
                ]
            );
        }
    }

    /**
     * Validate a process
     *
     * @param ProcessConfiguration $processConfiguration
     *
     * @throws \RuntimeException
     * @throws \CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException
     * @throws \CleverAge\ProcessBundle\Exception\CircularProcessException
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     */
    protected function checkProcess(ProcessConfiguration $processConfiguration): void
    {
        $taskConfigurations = $processConfiguration->getTaskConfigurations();
        $mainTaskList = $processConfiguration->getMainTaskGroup();
        $entryPoint = $processConfiguration->getEntryPoint();
        $endPoint = $processConfiguration->getEndPoint();

        // Check circular dependencies
        foreach ($taskConfigurations as $taskConfiguration) {
            if ($taskConfiguration->hasAncestor($taskConfiguration)) {
                throw CircularProcessException::create($processConfiguration->getCode(), $taskConfiguration->getCode());
            }
        }

        // Check multi-branch processes
        foreach ($taskConfigurations as $taskConfiguration) {
            if (!\in_array($taskConfiguration->getCode(), $mainTaskList, true)) {
                // We won't throw an error to ease development... but there must be some kind of warning
                $state = $taskConfiguration->getState();
                $logContext = $state->getLogContext();
                $logContext['main_task_list'] = $mainTaskList;
                $this->logger->warning("The task won't be executed", $logContext);
                $this->handleState($state);
            }
        }

        // Check coherence for entry/end points
        $processConfiguration->getEndPoint();
        if ($entryPoint && !\in_array($entryPoint->getCode(), $mainTaskList, true)) {
            throw InvalidProcessConfigurationException::createNotInMain($entryPoint, $mainTaskList);
        }
        if ($endPoint && !\in_array($endPoint->getCode(), $mainTaskList, true)) {
            throw InvalidProcessConfigurationException::createNotInMain($endPoint, $mainTaskList);
        }
    }

    /**
     * When an iterable task returns at least one element, it gets added here
     *
     * @param TaskConfiguration $taskConfiguration
     */
    protected function addProcessedIterable(TaskConfiguration $taskConfiguration): void
    {
        $this->processedIterables[$taskConfiguration->getCode()] = $taskConfiguration;
    }

    /**
     * If true this means that the tasks returned an element at least once
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @return bool
     */
    protected function hasProcessedIterable(TaskConfiguration $taskConfiguration): bool
    {
        return array_key_exists($taskConfiguration->getCode(), $this->processedIterables);
    }

    /**
     * Once everything was flushed, the task is resolved and can be removed from the stack
     *
     * @param TaskConfiguration $taskConfiguration
     */
    protected function removeProcessedIterable(TaskConfiguration $taskConfiguration): void
    {
        unset($this->processedIterables[$taskConfiguration->getCode()]);
    }

    /**
     * Add blocking tasks that were just processed
     *
     * @param TaskConfiguration $taskConfiguration
     */
    protected function addProcessedBlocking(TaskConfiguration $taskConfiguration): void
    {
        $this->processedBlockings[$taskConfiguration->getCode()] = $taskConfiguration;
    }

    /**
     * If true this means the task was processed normally but was never run with proceed
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @return bool
     */
    protected function hasProcessedBlocking(TaskConfiguration $taskConfiguration): bool
    {
        return array_key_exists($taskConfiguration->getCode(), $this->processedBlockings);
    }

    /**
     * Once a blocking task has been proceeded, we can remove it from the stack
     *
     * @param TaskConfiguration $taskConfiguration
     */
    protected function removeProcessedBlocking(TaskConfiguration $taskConfiguration): void
    {
        unset($this->processedBlockings[$taskConfiguration->getCode()]);
    }
}
