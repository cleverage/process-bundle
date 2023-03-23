<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Manager;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Event\ProcessEvent;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\InitializableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;
use UnexpectedValueException;

use function count;
use function in_array;

/**
 * Execute processes
 */
class ProcessManager
{
    protected const EXECUTE_PROCESS = 1;

    protected const EXECUTE_PROCEED = 2;

    protected const EXECUTE_FLUSH = 4;

    /**
     * @var TaskConfiguration
     */
    protected $blockingTaskConfiguration;

    /**
     * @var TaskConfiguration[]
     */
    protected $processedIterables = [];

    /**
     * @var TaskConfiguration[]
     */
    protected $processedBlockings = [];

    protected ?ProcessHistory $processHistory = null;

    protected ?TaskConfiguration $taskConfiguration = null;

    public function __construct(
        protected ContainerInterface $container,
        protected ProcessLogger $processLogger,
        protected TaskLogger $taskLogger,
        protected ProcessConfigurationRegistry $processConfigurationRegistry,
        protected ContextualOptionResolver $contextualOptionResolver,
        protected EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getProcessHistory(): ?ProcessHistory
    {
        return $this->processHistory;
    }

    public function getTaskConfiguration(): ?TaskConfiguration
    {
        return $this->taskConfiguration;
    }

    /**
     * Execute a process with a given input and context
     *
     * This method decorates the real execution to add event & error handling
     * @see ProcessManager::doExecute
     *
     * @param null $input
     *
     * @return mixed
     */
    public function execute(string $processCode, mixed $input = null, array $context = [])
    {
        try {
            $this->eventDispatcher->dispatch(new ProcessEvent($processCode, $input, $context));
            $this->processLogger->debug('Process start');

            $result = $this->doExecute($processCode, $input, $context);

            $this->processLogger->debug('Process end');
            $this->eventDispatcher->dispatch(new ProcessEvent($processCode, $input, $context, $result));
        } catch (Throwable $error) {
            $this->processLogger->critical('Critical process failure', [
                'error' => $error->getMessage(),
            ]);
            $this->eventDispatcher->dispatch(new ProcessEvent($processCode, $input, $context, null, $error));

            throw $error;
        }

        return $result;
    }

    /**
     * Real process execution, with a given input and context
     *
     * @return mixed
     */
    protected function doExecute(string $processCode, mixed $input = null, array $context = [])
    {
        $parentProcessHistory = $this->processHistory;
        $processConfiguration = $this->processConfigurationRegistry->getProcessConfiguration($processCode);
        $processHistory = $this->initializeStates($processConfiguration, $context);
        $this->processHistory = $processHistory;
        $this->checkProcess($processConfiguration);

        // First initialize the whole stack in a linear way, tasks are initialized in the order they are configured
        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->initialize($taskConfiguration);
        }

        // If defined, set the input of a task
        if ($processConfiguration->getEntryPoint()) {
            $processConfiguration->getEntryPoint()
                ->getState()
                ->setInput($input);
        } elseif ($input !== null) {
            $this->processLogger->warning('Process has no entry point for input');
        }

        // Resolve task from main branch, starting by the end
        $taskList = array_reverse($processConfiguration->getTaskConfigurations());
        $allowedTasks = $processConfiguration->getMainTaskGroup();
        foreach ($taskList as $taskConfiguration) {
            if (in_array($taskConfiguration->getCode(), $allowedTasks, true)) {
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
            $returnValue = $processConfiguration->getEndPoint()
                ->getState()
                ->getOutput();
        }

        $this->processHistory = $parentProcessHistory;

        return $returnValue;
    }

    /**
     * Resolve a task, by checking if parents are resolved and processing roots and BlockingTasks
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
            if (! $previousTasksConfiguration->getState()->isResolved()) {
                $isResolved = $this->resolve($previousTasksConfiguration);
                $allParentsResolved = $allParentsResolved && $isResolved;
            }
        }

        if (! $allParentsResolved) {
            throw new UnexpectedValueException('Cannot resolve all parents');
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

        // This task is now finished, we may flush it to test if there is anything lasting
        $this->flush($taskConfiguration);

        $state->setStatus(ProcessState::STATUS_RESOLVED);

        return $state->isResolved();
    }

    /**
     * Fetch task service and run additional setup for InitializableTasks
     */
    protected function initialize(TaskConfiguration $taskConfiguration): void
    {
        $this->taskConfiguration = $taskConfiguration;

        if ($taskConfiguration->getErrorStrategy() === TaskConfiguration::STRATEGY_STOP
            && (count($taskConfiguration->getErrorOutputs())) > 0) {
            $m = "Task configuration {$taskConfiguration->getCode()} has error outputs ";
            $m .= "but it's error strategy 'stop' implies they will never be reached.";
            $this->taskLogger->debug($m);
        }
        // @todo Refactor this using a Registry with this feature:
        // https://symfony.com/doc/current/service_container/service_subscribers_locators.html
        $serviceReference = $taskConfiguration->getServiceReference();
        if (str_starts_with($serviceReference, '@')) {
            $task = $this->container->get(ltrim($serviceReference, '@'));
        } elseif ($this->container->has($serviceReference)) {
            $task = $this->container->get($serviceReference);
        } else {
            throw new UnexpectedValueException(
                "Unable to resolve service reference for Task '{$taskConfiguration->getCode()}'"
            );
        }
        if (! $task instanceof TaskInterface) {
            throw new UnexpectedValueException(
                "Service defined in Task '{$taskConfiguration->getCode()}' is not a TaskInterface"
            );
        }
        $taskConfiguration->setTask($task);

        if ($task instanceof InitializableTaskInterface) {
            $state = $taskConfiguration->getState();
            try {
                $task->initialize($state);
            } catch (Throwable $e) {
                $logContext = [
                    'exception' => $e,
                ];
                $this->taskLogger->critical($e->getMessage(), $logContext);
                $state->stop($e);
            }
        }
        $this->handleState($taskConfiguration->getState());

        $this->taskConfiguration = null;
    }

    protected function process(TaskConfiguration $taskConfiguration, int $executionFlag = self::EXECUTE_PROCESS): void
    {
        $this->taskConfiguration = $taskConfiguration;

        $state = $taskConfiguration->getState();
        do {
            // Execute the current task, and fetch status
            $this->processExecution($taskConfiguration, $executionFlag);

            $this->handleState($state);

            // An error feed cannot be blocked or skipped (even if the process has been stopped)
            if ($state->hasErrorOutput()) {
                foreach ($taskConfiguration->getErrorTasksConfigurations() as $errorTask) {
                    $this->prepareNextProcess($taskConfiguration, $errorTask, true);
                    $this->process($errorTask);

                    // Bubble up the stop signal
                    if ($errorTask->getState()->isStopped()) {
                        $state->setStopped(true);

                        return;
                    }
                }
            }
            if ($state->isStopped()) {
                $exception = $state->getException();
                if ($exception) {
                    $m = "Process {$state->getProcessConfiguration()
                        ->getCode()} has failed";
                    $m .= " during process {$state->getTaskConfiguration()
                        ->getCode()}";
                    $m .= " with message: '{$exception->getMessage()}'.\n";
                    throw new FatalError(
                        $m,
                        -1,
                        [
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'type' => 500,
                            'message' => $exception->getMessage()
                        ]
                    );
                }

                return;
            }

            // Run child items only if the state is not "skipped" and task is not blocking
            $task = $taskConfiguration->getTask();
            $shouldContinue =
                (! $task instanceof BlockingTaskInterface || $executionFlag === self::EXECUTE_PROCEED)
                && ! $state->isSkipped();

            if ($shouldContinue) {
                if ($task instanceof IterableTaskInterface) {
                    // Register the task as not empty
                    $this->addProcessedIterable($taskConfiguration);
                }
                foreach ($taskConfiguration->getNextTasksConfigurations() as $nextTaskConfiguration) {
                    $this->prepareNextProcess($taskConfiguration, $nextTaskConfiguration);
                    $this->process($nextTaskConfiguration);

                    // Bubble up the stop signal
                    if ($nextTaskConfiguration->getState()->isStopped()) {
                        $state->setStopped(true);

                        return;
                    }
                }
                $state->setSkipped(false); // Reset skipped state
            }

            $hasMoreItem = false; // By default, a task is not iterable
            if ($task instanceof IterableTaskInterface) {
                // Check if task has more items
                $hasMoreItem = $task->next($state);
                if (! $hasMoreItem) {
                    if (! $this->hasProcessedIterable($taskConfiguration)) {
                        return; // This means the task is empty
                    }
                    // This means we are over iterating this task so we can remove it from registry
                    $this->removeProcessedIterable($taskConfiguration);
                    if ($executionFlag !== self::EXECUTE_FLUSH) {
                        // This task is now finished, we may flush it to test if there is anything lasting
                        $this->flush($taskConfiguration);
                    }
                    if ($state->isStopped()) {
                        return;
                    }
                }
            }
        } while ($hasMoreItem);

        $this->taskConfiguration = null;
    }

    protected function processExecution(TaskConfiguration $taskConfiguration, int $executionFlag): void
    {
        $task = $taskConfiguration->getTask();
        if ($task === null) {
            throw new RuntimeException("Missing task for configuration {$taskConfiguration->getCode()}");
        }
        $state = $taskConfiguration->getState();

        try {
            if ($executionFlag === self::EXECUTE_PROCESS) {
                $state->reset(false);
                $this->processLogger->debug("Processing task {$taskConfiguration->getCode()}");
                $task->execute($state);
                if ($task instanceof BlockingTaskInterface) {
                    $this->addProcessedBlocking($taskConfiguration);
                }
            } elseif ($executionFlag === self::EXECUTE_PROCEED) {
                $state->reset(true);
                if (! $task instanceof BlockingTaskInterface) {
                    // This exception should never be thrown
                    throw new UnexpectedValueException("Task {$taskConfiguration->getCode()} is not blocking");
                }
                $this->processLogger->debug("Proceeding task {$taskConfiguration->getCode()}");
                $task->proceed($state);
                $this->removeProcessedBlocking($taskConfiguration);
            } elseif ($executionFlag === self::EXECUTE_FLUSH) {
                $state->reset(true);
                if (! $task instanceof FlushableTaskInterface) {
                    // This exception should never be thrown
                    throw new UnexpectedValueException("Task {$taskConfiguration->getCode()} is not flushable");
                }
                $this->processLogger->debug("Flushing task {$taskConfiguration->getCode()}");
                $task->flush($state);
            } else {
                throw new UnexpectedValueException("Unknown execution flag: $executionFlag");
            }

            $exception = $state->getException();
        } catch (Throwable $e) {
            $exception = $e;
        }

        // Manage exception catching and setting the same
        if ($exception) {
            $this->taskLogger->log(
                $taskConfiguration->getLogLevel(),
                $exception->getMessage(),
                $state->getErrorContext()
            );
            $state->setException($exception);
            if (! $state->hasErrorOutput()) {
                $state->setErrorOutput($state->getInput());
            }
            if ($taskConfiguration->getErrorStrategy() === TaskConfiguration::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($taskConfiguration->getErrorStrategy() === TaskConfiguration::STRATEGY_STOP) {
                $state->stop($exception);
            } else {
                throw new UnexpectedValueException(
                    "Unknown error strategy '{$taskConfiguration->getErrorStrategy()}'"
                );
            }
        }
    }

    /**
     * Browse all children for FlushableTask until a BlockingTask is found
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

    protected function finalize(TaskConfiguration $taskConfiguration): void
    {
        $task = $taskConfiguration->getTask();
        if ($task instanceof FinalizableTaskInterface) {
            $this->taskConfiguration = $taskConfiguration;
            $state = $taskConfiguration->getState();
            try {
                $task->finalize($taskConfiguration->getState());
            } catch (Throwable $e) {
                $logContext = [
                    'exception' => $e,
                ];
                $this->taskLogger->critical($e->getMessage(), $logContext);
                $state->stop($e);
            }
            $this->handleState($state);
            $this->taskConfiguration = null;
        }
    }

    protected function initializeStates(
        ProcessConfiguration $processConfiguration,
        array $context = []
    ): ProcessHistory {
        $processHistory = new ProcessHistory($processConfiguration, $context);

        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $state = new ProcessState($processConfiguration, $processHistory);
            $state->setContext($context);
            $state->setContextualOptionResolver($this->contextualOptionResolver);

            $taskConfiguration->setState($state);
            $state->setTaskConfiguration($taskConfiguration);
        }

        return $processHistory;
    }

    protected function prepareNextProcess(
        TaskConfiguration $previousTaskConfiguration,
        TaskConfiguration $nextTaskConfiguration,
        bool $isError = false
    ): void {
        if ($isError) {
            $input = $previousTaskConfiguration->getState()
                ->getErrorOutput();
        } else {
            $input = $previousTaskConfiguration->getState()
                ->getOutput();
        }

        $nextState = $nextTaskConfiguration->getState();
        $nextState->setInput($input);
        $nextState->setPreviousState($previousTaskConfiguration->getState());
    }

    /**
     * Save the state of the import process
     */
    protected function handleState(ProcessState $state): void
    {
        $processHistory = $state->getProcessHistory();
        if ($state->getException() && $state->isStopped()) {
            $processHistory->setFailed();
        }
    }

    protected function endProcess(ProcessHistory $history): void
    {
        // Do not change state if already set
        if ($history->isStarted()) {
            $history->setSuccess();

            $this->processLogger->info(
                "Process {$history->getProcessCode()} succeed",
                [
                    'duration' => $history->getDuration(),
                ]
            );
        }
    }

    /**
     * Validate a process
     */
    protected function checkProcess(ProcessConfiguration $processConfiguration): void
    {
        $processConfiguration->checkCircularDependencies();

        $taskConfigurations = $processConfiguration->getTaskConfigurations();
        $mainTaskList = $processConfiguration->getMainTaskGroup();
        $entryPoint = $processConfiguration->getEntryPoint();
        $endPoint = $processConfiguration->getEndPoint();

        // Check multi-branch processes
        foreach ($taskConfigurations as $taskConfiguration) {
            if (! in_array($taskConfiguration->getCode(), $mainTaskList, true)) {
                // We won't throw an error to ease development... but there must be some kind of warning
                $state = $taskConfiguration->getState();
                $logContext = [
                    'main_task_list' => $mainTaskList,
                ];
                $this->processLogger->warning(
                    "Task '{$taskConfiguration->getCode()}' is unreachable, check that it's referenced in some other task output or in the main entry point",
                    $logContext
                );
                $this->handleState($state);
            }
        }

        // Check coherence for entry/end points
        $processConfiguration->getEndPoint();
        if ($entryPoint && ! in_array($entryPoint->getCode(), $mainTaskList, true)) {
            throw InvalidProcessConfigurationException::createNotInMain(
                $processConfiguration,
                $entryPoint,
                $mainTaskList
            );
        }
        if ($endPoint && ! in_array($endPoint->getCode(), $mainTaskList, true)) {
            throw InvalidProcessConfigurationException::createNotInMain(
                $processConfiguration,
                $endPoint,
                $mainTaskList
            );
        }
    }

    /**
     * When an iterable task returns at least one element, it gets added here
     */
    protected function addProcessedIterable(TaskConfiguration $taskConfiguration): void
    {
        $this->processedIterables[$taskConfiguration->getCode()] = $taskConfiguration;
    }

    /**
     * If true this means that the tasks returned an element at least once
     */
    protected function hasProcessedIterable(TaskConfiguration $taskConfiguration): bool
    {
        return array_key_exists($taskConfiguration->getCode(), $this->processedIterables);
    }

    /**
     * Once everything was flushed, the task is resolved and can be removed from the stack
     */
    protected function removeProcessedIterable(TaskConfiguration $taskConfiguration): void
    {
        unset($this->processedIterables[$taskConfiguration->getCode()]);
    }

    /**
     * Add blocking tasks that were just processed
     */
    protected function addProcessedBlocking(TaskConfiguration $taskConfiguration): void
    {
        $this->processedBlockings[$taskConfiguration->getCode()] = $taskConfiguration;
    }

    /**
     * If true this means the task was processed normally but was never run with proceed
     */
    protected function hasProcessedBlocking(TaskConfiguration $taskConfiguration): bool
    {
        return array_key_exists($taskConfiguration->getCode(), $this->processedBlockings);
    }

    /**
     * Once a blocking task has been proceeded, we can remove it from the stack
     */
    protected function removeProcessedBlocking(TaskConfiguration $taskConfiguration): void
    {
        unset($this->processedBlockings[$taskConfiguration->getCode()]);
    }
}
