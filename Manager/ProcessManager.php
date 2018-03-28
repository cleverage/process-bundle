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
use CleverAge\ProcessBundle\Entity\TaskHistory;
use CleverAge\ProcessBundle\Exception\CircularProcessException;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\InitializableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Doctrine\ORM\EntityManager;
use CleverAge\ProcessBundle\Entity\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Execute processes
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessManager
{
    /** @var ContainerInterface */
    protected $container;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ProcessConfigurationRegistry */
    protected $processConfigurationRegistry;

    /** @var TaskConfiguration */
    protected $blockingTaskConfiguration;

    /**
     * @param ContainerInterface           $container
     * @param LoggerInterface              $logger
     * @param EntityManager                $entityManager
     * @param ProcessConfigurationRegistry $processConfigurationRegistry
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        EntityManager $entityManager,
        ProcessConfigurationRegistry $processConfigurationRegistry
    )
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->processConfigurationRegistry = $processConfigurationRegistry;
    }

    /**
     * @param string          $processCode
     * @param OutputInterface $output
     * @param mixed           $input
     *
     * @throws \Exception
     *
     * @return int
     */
    public function execute(string $processCode, OutputInterface $output = null, $input = null)
    {
        $processConfiguration = $this->processConfigurationRegistry->getProcessConfiguration($processCode);
        $processHistory = $this->initializeStates($processConfiguration, $output);
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
            if (in_array($taskConfiguration->getCode(), $allowedTasks)) {
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
     * @TODO handle properly resolution of stopped task
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @return bool
     */
    protected function resolve(TaskConfiguration $taskConfiguration)
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
            throw new \UnexpectedValueException("Cannot resolve all parents");
        }

        $state->setStatus(ProcessState::STATUS_PROCESSING);

        // Start processing only roots that are not in error branch
        if ($taskConfiguration->isRoot()) {
            $this->process($taskConfiguration);
        }

        // Then we can process BlockingTask
        if ($taskConfiguration->getTask() instanceof BlockingTaskInterface) {
            $this->process($taskConfiguration, true);
        }

        $state->setStatus(ProcessState::STATUS_RESOLVED);

        return $state->isResolved();
    }

    /**
     * Fetch task service and run additional setup for InitializableTasks
     *
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \Exception
     */
    protected function initialize(TaskConfiguration $taskConfiguration)
    {
        $serviceReference = $taskConfiguration->getServiceReference();
        if (strpos($serviceReference, '@') === 0) {
            $task = $this->container->get(ltrim($serviceReference, '@'));
        } elseif (class_exists($serviceReference)) {
            $task = new $serviceReference();
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
                $state->log($e->getMessage(), LogLevel::CRITICAL, \get_class($e));
                $state->stop($e);
            }
        }
        $this->handleState($taskConfiguration->getState());
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param bool              $doProceed
     *
     * @throws \Exception
     */
    protected function process(TaskConfiguration $taskConfiguration, $doProceed = false)
    {
        $state = $taskConfiguration->getState();
        do {
            // Execute the current task, and fetch status
            if (!$doProceed) {
                $this->doProcessTask($taskConfiguration);
            } else {
                $this->doProceedTask($taskConfiguration);
            }
            $this->handleState($state);
            if ($state->isStopped()) {
                return;
            }

            // An error feed cannot be blocked or skipped (except if the process has been stopped)
            if ($state->hasError()) {
                foreach ($taskConfiguration->getErrorTasksConfigurations() as $errorTask) {
                    $this->prepareNextProcess($taskConfiguration, $errorTask, true);
                    $this->process($errorTask);
                    /** @noinspection DisconnectedForeachInstructionInspection */
                    if ($state->isStopped()) {
                        return;
                    }
                }
            }

            // Run child items only if the state is not "skipped" and task is not blocking
            $task = $taskConfiguration->getTask();
            $shouldContinue = (!$task instanceof BlockingTaskInterface || $doProceed) && !$state->isSkipped();

            if ($shouldContinue) {
                foreach ($taskConfiguration->getNextTasksConfigurations() as $nextTask) {
                    $this->prepareNextProcess($taskConfiguration, $nextTask);
                    $this->process($nextTask);
                    /** @noinspection DisconnectedForeachInstructionInspection */
                    if ($state->isStopped()) {
                        return;
                    }
                }
                $state->setSkipped(false); // Reset skipped state
            }

            $hasMoreItem = false; // By default, a task is not iterable
            if ($task instanceof IterableTaskInterface) {
                $hasMoreItem = $task->next($state); // Check if task has more items
            }
        } while ($hasMoreItem);
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \Exception
     */
    protected function finalize(TaskConfiguration $taskConfiguration)
    {
        $state = $taskConfiguration->getState();
        $task = $taskConfiguration->getTask();
        if ($task instanceof FinalizableTaskInterface) {
            try {
                $task->finalize($taskConfiguration->getState());
            } catch (\Throwable $e) {
                $state->log($e->getMessage(), LogLevel::CRITICAL, \get_class($e));
                $state->stop($e);
            }
        }

        $this->handleState($state);
    }

    /**
     * @param ProcessConfiguration $processConfiguration
     * @param OutputInterface      $output
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     *
     * @return ProcessHistory
     */
    protected function initializeStates(ProcessConfiguration $processConfiguration, OutputInterface $output = null)
    {
        $processHistory = new ProcessHistory($processConfiguration);
        $this->entityManager->persist($processHistory);
        $this->entityManager->flush($processHistory);

        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $state = new ProcessState($processConfiguration, $processHistory);
            if ($output) {
                $state->setConsoleOutput($output);
            }

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
    protected function prepareNextProcess(TaskConfiguration $previousTaskConfiguration, TaskConfiguration $nextTaskConfiguration, $isError = false)
    {
        if (!$isError) {
            $input = $previousTaskConfiguration->getState()->getOutput();
        } else {
            $input = $previousTaskConfiguration->getState()->getError();
        }

        $nextState = $nextTaskConfiguration->getState();
        $nextState->setInput($input);
        $nextState->setPreviousState($previousTaskConfiguration->getState());
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     */
    protected function doProcessTask(TaskConfiguration $taskConfiguration)
    {
        $state = $taskConfiguration->getState();
        $state->setOutput(null);
        $state->setSkipped(false);

        $consoleOutput = $state->getConsoleOutput();
        if ($consoleOutput && $consoleOutput->isDebug()) {
            $consoleOutput->writeln("<info>Processing task {$taskConfiguration->getCode()}</info>");
        }

        $task = $taskConfiguration->getTask();
        try {
            $task->execute($state);
        } catch (\Throwable $e) {
            $state->log($e->getMessage(), LogLevel::CRITICAL, \get_class($e));
            $state->stop($e);
        }
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     *
     * @throws \UnexpectedValueException
     */
    protected function doProceedTask(TaskConfiguration $taskConfiguration)
    {
        $state = $taskConfiguration->getState();
        $state->setInput(null);
        $state->setOutput(null);
        $state->setPreviousState(null);
        $state->setSkipped(false);

        $consoleOutput = $state->getConsoleOutput();
        if ($consoleOutput && $consoleOutput->isDebug()) {
            $consoleOutput->writeln("<info>Proceeding task {$taskConfiguration->getCode()}</info>");
        }

        $task = $taskConfiguration->getTask();
        if (!$task instanceof BlockingTaskInterface) {
            // This exception should never be thrown
            throw new \UnexpectedValueException("Task {$taskConfiguration->getCode()} is not blocking");
        }

        try {
            $task->proceed($state);
        } catch (\Throwable $e) {
            $state->log($e->getMessage(), LogLevel::CRITICAL, \get_class($e));
            $state->stop($e);
        }
    }

    /**
     * Save the state of the import process
     *
     * @param ProcessState $state
     *
     * @throws \Exception
     */
    protected function handleState(ProcessState $state)
    {
        if ($this->entityManager->isOpen()) {
            // Merging the process history back into the unit of work
            /** @var ProcessHistory $processHistory */
            $processHistory = $this->entityManager->merge($state->getProcessHistory());
        } else {
            $processHistory = $state->getProcessHistory(); // We will crash later
        }

        $consoleOutput = $state->getConsoleOutput();
        foreach ($state->getTaskHistories() as $taskHistory) {
            if ($consoleOutput && ($consoleOutput->isVerbose() || !$this->entityManager->isOpen())) {
                switch ($taskHistory->getLevel()) {
                    case LogLevel::WARNING:
                    case LogLevel::NOTICE:
                        $level = 'comment';
                        break;
                    case LogLevel::INFO:
                    case LogLevel::DEBUG:
                        $level = 'info';
                        break;
                    default:
                        $level = 'error';
                }
                $msg = "<{$level}>{$taskHistory->getTaskCode()} ({$taskHistory->getLevel()}): ";
                $msg .= "{$taskHistory->getMessage()} [{$taskHistory->getReference()}]</{$level}>";
                $consoleOutput->writeln($msg);
                $consoleOutput->writeln(json_encode($taskHistory->getContext()));
            }
            $taskHistory->setProcessHistory($processHistory);
            $processHistory->addTaskHistory($taskHistory);
            if ($this->entityManager->isOpen()) {
                $this->entityManager->persist($taskHistory);
                $this->entityManager->flush($taskHistory);
            }
        }

        if (!$this->entityManager->isOpen()) {
            throw new \RuntimeException('Doctrine has closed the entity manager, aborting process');
        }
        $state->clearTaskHistories();
        $this->entityManager->clear(TaskHistory::class);

        if ($state->getException()) {
            $processHistory->setFailed();
            $this->entityManager->flush($processHistory);

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
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    protected function endProcess(ProcessHistory $history)
    {
        /** @var ProcessHistory $processHistory */
        $processHistory = $this->entityManager->merge($history);

        // Do not change state if already set
        if ($processHistory->isStarted()) {
            $processHistory->setSuccess();
        }

        $this->entityManager->flush($processHistory);
    }

    /**
     * Validate a process
     *
     * @param ProcessConfiguration $processConfiguration
     */
    protected function checkProcess(ProcessConfiguration $processConfiguration)
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
            if (!in_array($taskConfiguration->getCode(), $mainTaskList)) {
                // We won't throw an error to ease development... but there must be some kind of warning
                $state = $taskConfiguration->getState();
                $state->log("The task won't be executed", LogLevel::WARNING, null, $mainTaskList);
                $this->handleState($state);
            }
        }

        // Check coherence for entry/end points
        $processConfiguration->getEndPoint();
        if ($entryPoint && !in_array($entryPoint->getCode(), $mainTaskList)) {
            throw InvalidProcessConfigurationException::createNotInMain($entryPoint, $mainTaskList);
        }
        if ($endPoint && !in_array($endPoint->getCode(), $mainTaskList)) {
            throw InvalidProcessConfigurationException::createNotInMain($endPoint, $mainTaskList);
        }
    }
}
