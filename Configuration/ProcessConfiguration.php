<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Configuration;

use CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException;

/**
 * Holds the processes configuration to launch a task
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessConfiguration
{
    /** @var string */
    protected $code;

    /** @var array */
    protected $options = [];

    /** @var TaskConfiguration */
    protected $entryPoint;

    /** @var TaskConfiguration */
    protected $endPoint;

    /** @var TaskConfiguration[] */
    protected $taskConfigurations;

    /** @var array */
    protected $dependencyGroups;

    /** @var array */
    protected $mainTaskGroup;

    /**
     * @param string              $code
     * @param TaskConfiguration[] $taskConfigurations
     * @param array               $options
     * @param string              $entryPoint
     * @param string              $endPoint
     */
    public function __construct(
        $code,
        array $taskConfigurations,
        array $options = [],
        $entryPoint = null,
        $endPoint = null
    ) {
        $this->code = $code;
        $this->taskConfigurations = $taskConfigurations;
        $this->options = $options;
        $this->entryPoint = $entryPoint;
        $this->endPoint = $endPoint;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration|null
     */
    public function getEntryPoint()
    {
        if (null === $this->entryPoint) {
            return null;
        }

        return $this->getTaskConfiguration($this->entryPoint);
    }

    /**
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration|null
     */
    public function getEndPoint()
    {
        if (null === $this->endPoint) {
            return null;
        }

        return $this->getTaskConfiguration($this->endPoint);
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getTaskConfigurations(): array
    {
        return $this->taskConfigurations;
    }

    /**
     * @param string $taskCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration
     */
    public function getTaskConfiguration(string $taskCode): TaskConfiguration
    {
        if (!array_key_exists($taskCode, $this->taskConfigurations)) {
            throw new MissingTaskConfigurationException($taskCode);
        }

        return $this->taskConfigurations[$taskCode];
    }

    /**
     * Group all task by dependencies
     *
     * If one task depend from another, it should come after
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return array
     */
    public function getDependencyGroups(): array
    {
        if (null === $this->dependencyGroups) {
            $this->dependencyGroups = [];
            foreach ($this->getTaskConfigurations() as $taskConfiguration) {
                $isInBranch = false;
                foreach ($this->dependencyGroups as $branch) {
                    if (\in_array($taskConfiguration->getCode(), $branch, true)) {
                        $isInBranch = true;
                        break;
                    }
                }

                if (!$isInBranch) {
                    $dependencies = $this->buildDependencies($taskConfiguration);
                    $dependencies = $this->sortDependencies($dependencies);

                    $this->dependencyGroups[] = $dependencies;
                }
            }
        }

        return $this->dependencyGroups;
    }

    /**
     * Get the main task group that will be executed
     * It may be defined by the entry_point, or the end_point or simply the first task
     *
     * If one task depend from another, it should come after
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return array
     */
    public function getMainTaskGroup(): array
    {
        if (null === $this->mainTaskGroup) {
            $mainTask = $this->getMainTask();

            foreach ($this->getDependencyGroups() as $branch) {
                if (\in_array($mainTask->getCode(), $branch, true)) {
                    $this->mainTaskGroup = $branch;
                    break;
                }
            }
        }

        return $this->mainTaskGroup;
    }

    /**
     * Get the most important task (may be the entry or end task, or simply the first)
     * Used to check which tree should be used
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration
     */
    public function getMainTask()
    {
        $entryTask = $this->getEntryPoint();
        if (!$entryTask) {
            $entryTask = $this->getEndPoint();
        }
        if (!$entryTask) {
            $entryTask = reset($this->taskConfigurations);
        }

        return $entryTask;
    }

    /**
     * Cross all relations of a task to find all dependencies, and append them to the given array
     *
     * @param TaskConfiguration $taskConfig
     * @param array             $dependencies
     *
     * @return array
     */
    protected function buildDependencies(TaskConfiguration $taskConfig, array &$dependencies = [])
    {
        $code = $taskConfig->getCode();

        // May have been added by previous task
        if (!\in_array($code, $dependencies, true)) {
            $dependencies[] = $code;

            foreach ($taskConfig->getPreviousTasksConfigurations() as $previousTasksConfig) {
                $this->buildDependencies($previousTasksConfig, $dependencies);
            }

            foreach ($taskConfig->getNextTasksConfigurations() as $nextTasksConfig) {
                $this->buildDependencies($nextTasksConfig, $dependencies);
            }

            foreach ($taskConfig->getErrorTasksConfigurations() as $errorTasksConfig) {
                $this->buildDependencies($errorTasksConfig, $dependencies);
            }
        }

        return $dependencies;
    }

    /**
     * Sort the tasks by dependencies
     *
     * @param array $dependencies
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return array
     */
    protected function sortDependencies(array $dependencies)
    {
        if (\count($dependencies) <= 1) {
            return $dependencies;
        }

        /** @var int $midOffset */
        $midOffset = \count($dependencies) / 2;
        $midTaskCode = $dependencies[$midOffset];
        $midTask = $this->getTaskConfiguration($midTaskCode);

        $previousTasks = [];
        $independentTasks = [];
        $nextTasks = [];

        foreach ($dependencies as $taskCode) {
            if ($taskCode !== $midTaskCode) {
                $task = $this->getTaskConfiguration($taskCode);

                if ($midTask->hasAncestor($task)) {
                    $previousTasks[] = $taskCode;
                } elseif ($midTask->hasDescendant($task)) {
                    $nextTasks[] = $taskCode;
                } else {
                    $independentTasks[] = $taskCode;
                }
            }
        }

        $previousTasks = $this->sortDependencies($previousTasks);
        $independentTasks = $this->sortDependencies($independentTasks);
        $nextTasks = $this->sortDependencies($nextTasks);

        return array_merge($previousTasks, [$midTaskCode], $independentTasks, $nextTasks);
    }
}
