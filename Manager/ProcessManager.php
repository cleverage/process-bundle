<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Manager;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
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
    ) {
        $this->container = $container;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->processConfigurationRegistry = $processConfigurationRegistry;
    }

    /**
     * @param string          $processCode
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return int
     */
    public function execute(string $processCode, OutputInterface $output = null)
    {
        $processConfiguration = $this->processConfigurationRegistry->getProcessConfiguration($processCode);
        $state = $this->initializeState($processConfiguration, $output);

        // First initialize the whole stack in a linear way, tasks are initialized in the order they are configured
        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->initialize($taskConfiguration, $state);
        }

        // Fetch first task to execute
        $taskConfiguration = $processConfiguration->getEntryPoint();

        // Then launch the process : iterate the tasks tree properly
        $this->process($taskConfiguration, $state);

        // Finalize the process in a linear way
        foreach ($processConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->finalize($taskConfiguration, $state);
        }

        $this->endProcess($state);

        return $state->getReturnCode();
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     *
     * @throws \Exception
     */
    protected function initialize(TaskConfiguration $taskConfiguration, ProcessState $state)
    {
        $this->doInitializeTask($taskConfiguration, $state);
        $this->handleState($state);
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     * @param mixed             $input
     *
     * @throws \Exception
     */
    protected function process(TaskConfiguration $taskConfiguration, ProcessState $state, $input = null)
    {
        $state = clone $state; // This is probably a bad idea but we can't just keep the reference
        do {
            $this->doProcessTask($taskConfiguration, $state, $input);
            $output = $state->getOutput();
            $error = $state->getError();
            $this->handleState($state);

            $task = $taskConfiguration->getTask();

            // Run child items only if the state is not "skipped" and task is not blocking
            if (!$task instanceof BlockingTaskInterface) {
                if ($state->isSkipped()) { // If skipped
                    if (null !== $error) {
                        foreach ($state->getProcessConfiguration()->getErrorTasks($taskConfiguration) as $errorTask) {
                            $this->process($errorTask, $state, $error);
                        }
                        $state->setError(null);
                    }
                } else {
                    foreach ($state->getProcessConfiguration()->getNextTasks($taskConfiguration) as $nextTask) {
                        $this->process($nextTask, $state, $output);
                    }
                }
                $state->setSkipped(false); // Reset skipped state
            }

            $hasMoreItem = false; // By default, a task is not iterable
            if ($task instanceof IterableTaskInterface) {
                $hasMoreItem = $task->next($state); // Check if task has more items
                if (!$hasMoreItem && $this->blockingTaskConfiguration) {
                    // If the task has no more items but there is a blocking task waiting for this one to end
                    // proceed with the blocking task
                    $this->proceed($this->blockingTaskConfiguration, $state);
                }
            }
        } while ($hasMoreItem);
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     *
     * @throws \Exception
     */
    protected function finalize(TaskConfiguration $taskConfiguration, ProcessState $state)
    {
        $this->doFinalizeTask($taskConfiguration, $state);
        $this->handleState($state);

        foreach ($state->getProcessConfiguration()->getNextTasks($taskConfiguration) as $nextTask) {
            $this->finalize($nextTask, $state);
        }
    }

    /**
     * @param ProcessConfiguration $processConfiguration
     * @param OutputInterface      $output
     *
     * @throws \InvalidArgumentException
     *
     * @return ProcessState
     */
    protected function initializeState(ProcessConfiguration $processConfiguration, OutputInterface $output = null)
    {
        $processHistory = new ProcessHistory($processConfiguration);
        $this->entityManager->persist($processHistory);
        $state = new ProcessState($processConfiguration, $processHistory);
        $state->setConsoleOutput($output);

        return $state;
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \UnexpectedValueException
     */
    protected function doInitializeTask(TaskConfiguration $taskConfiguration, ProcessState $state)
    {
        $state->setTaskConfiguration($taskConfiguration);
        $task = $this->container->get(ltrim($taskConfiguration->getServiceReference(), '@'));
        if (!$task instanceof TaskInterface) {
            throw new \UnexpectedValueException("Task '{$taskConfiguration->getCode()}' is not a TaskInterface");
        }
        $taskConfiguration->setTask($task);

        if ($task instanceof InitializableTaskInterface) {
            try {
                $task->initialize($state);
            } catch (\Exception $e) {
                $state->log($e->getMessage(), LogLevel::CRITICAL);
                $state->stop($e);
            }
        }
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     * @param mixed             $input
     *
     * @return TaskInterface
     */
    protected function preProcess(TaskConfiguration $taskConfiguration, ProcessState $state, $input)
    {
        $state->setInput($input);
        $state->setOutput(null);
        $state->setTaskConfiguration($taskConfiguration);

        $consoleOutput = $state->getConsoleOutput();
        if ($consoleOutput && $consoleOutput->isDebug()) {
            $consoleOutput->writeln("<info>Processing task {$taskConfiguration->getCode()}</info>");
        }

        $task = $taskConfiguration->getTask();
        if ($task instanceof BlockingTaskInterface) {
            $this->blockingTaskConfiguration = $taskConfiguration;
        }

        return $task;
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     * @param mixed             $input
     */
    protected function doProcessTask(TaskConfiguration $taskConfiguration, ProcessState $state, $input)
    {
        $task = $this->preProcess($taskConfiguration, $state, $input);

        try {
            $task->execute($state);
        } catch (\Exception $e) {
            $state->log($e->getMessage(), LogLevel::CRITICAL);
            $state->stop($e);
        }
    }

    /**
     * @param $taskConfiguration
     * @param $state
     */
    protected function doFinalizeTask(TaskConfiguration $taskConfiguration, ProcessState $state)
    {
        $state->setTaskConfiguration($taskConfiguration);
        $task = $taskConfiguration->getTask();
        if ($task instanceof FinalizableTaskInterface) {
            try {
                $task->finalize($state);
            } catch (\Exception $e) {
                $state->log($e->getMessage(), LogLevel::CRITICAL);
                $state->stop($e);
            }
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
        // Merging the process history back into the unit of work
        /** @var ProcessHistory $processHistory */
        $processHistory = $this->entityManager->merge($state->getProcessHistory());

        $consoleOutput = $state->getConsoleOutput();
        foreach ($state->getTaskHistories() as $taskHistory) {
            if ($consoleOutput && $consoleOutput->isVerbose()) {
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
            $this->entityManager->persist($taskHistory);
            $this->entityManager->flush($taskHistory);
        }
        $state->clearTaskHistories();

        if ($state->getException() || $state->isStopped()) {
            $processHistory->setFailed();
            $this->entityManager->flush($processHistory);

            throw new \RuntimeException(
                "Process {$state->getProcessConfiguration()->getCode()} as failed",
                -1,
                $state->getException()
            );
        }
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    protected function endProcess(ProcessState $state)
    {
        $processHistory = $this->entityManager->merge($state->getProcessHistory());
        $processHistory->setSuccess();
        $this->entityManager->flush($processHistory);
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     *
     * @throws \Exception
     */
    protected function proceed(TaskConfiguration $taskConfiguration, ProcessState $state)
    {
        $state = clone $state;
        $output = $this->doProceedTask($taskConfiguration, $state);
        $this->handleState($state);

        // Run child items only if the state is not "skipped"
        if (!$state->isSkipped()) {
            foreach ($state->getProcessConfiguration()->getNextTasks($taskConfiguration) as $nextTask) {
                $this->process($nextTask, $state, $output);
            }
        }
        $state->setSkipped(false); // Reset skipped state
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     * @param ProcessState      $state
     *
     * @throws \UnexpectedValueException
     *
     * @return mixed
     */
    protected function doProceedTask(TaskConfiguration $taskConfiguration, ProcessState $state)
    {
        $task = $this->preProcess($taskConfiguration, $state, null);
        $this->blockingTaskConfiguration = null;
        if (!$task instanceof BlockingTaskInterface) {
            // This exception should never be thrown
            throw new \UnexpectedValueException("Task {$taskConfiguration->getCode()} is not blocking");
        }

        try {
            $task->proceed($state);
        } catch (\Exception $e) {
            $state->log($e->getMessage(), LogLevel::CRITICAL);
            $state->stop($e);
        }

        return $state->getOutput();
    }
}
