<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Configuration;

use CleverAge\ProcessBundle\Exception\CircularProcessException;
use CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException;

/**
 * Holds the processes configuration to launch a task.
 */
class ProcessConfiguration
{
    protected ?array $dependencyGroups = null;

    protected ?array $mainTaskGroup = null;

    public function __construct(
        protected string $code,
        protected array $taskConfigurations,
        protected array $options = [],
        protected ?string $entryPoint = null,
        protected ?string $endPoint = null,
        protected string $description = '',
        protected string $help = '',
        protected bool $public = true
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getEntryPoint(): ?TaskConfiguration
    {
        if (null === $this->entryPoint) {
            return null;
        }

        return $this->getTaskConfiguration($this->entryPoint);
    }

    public function getEndPoint(): ?TaskConfiguration
    {
        if (null === $this->endPoint) {
            return null;
        }

        return $this->getTaskConfiguration($this->endPoint);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isPrivate(): bool
    {
        return !$this->public;
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getTaskConfigurations(): array
    {
        return $this->taskConfigurations;
    }

    public function getTaskConfiguration(string $taskCode): TaskConfiguration
    {
        if (!\array_key_exists($taskCode, $this->taskConfigurations)) {
            throw MissingTaskConfigurationException::create($taskCode);
        }

        return $this->taskConfigurations[$taskCode];
    }

    /**
     * Group all task by dependencies.
     *
     * If one task depend from another, it should come after
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
     * It may be defined by the entry_point, or the end_point or simply the first task.
     *
     * If one task depend from another, it should come after
     */
    public function getMainTaskGroup(): array
    {
        if (null === $this->mainTaskGroup) {
            $this->mainTaskGroup = [];
            $mainTask = $this->getMainTask();

            foreach ($this->getDependencyGroups() as $branch) {
                if (\in_array($mainTask?->getCode(), $branch, true)) {
                    $this->mainTaskGroup = $branch;
                    break;
                }
            }
        }

        return $this->mainTaskGroup;
    }

    /**
     * Get the most important task (may be the entry or end task, or simply the first)
     * Used to check which tree should be used.
     */
    public function getMainTask(): ?TaskConfiguration
    {
        $entryTask = $this->getEntryPoint();

        // If there's no entry point, we might use the end point
        if (!$entryTask) {
            $entryTask = $this->getEndPoint();
        }

        // By default use the first defined task
        if (!$entryTask) {
            $entryTask = reset($this->taskConfigurations);
        }

        // May happen with an empty array
        if (false === $entryTask) {
            return null;
        }

        return $entryTask;
    }

    /**
     * Assert the process does not contain circular dependencies.
     */
    public function checkCircularDependencies(): void
    {
        $taskConfigurations = $this->getTaskConfigurations();

        foreach ($taskConfigurations as $taskConfiguration) {
            foreach ($taskConfiguration->getPreviousTasksConfigurations() as $previousTaskConfig) {
                if ($taskConfiguration->getCode() === $previousTaskConfig->getCode()) {
                    throw CircularProcessException::create($this->getCode(), $taskConfiguration->getCode());
                }
            }
            if ($taskConfiguration->hasAncestor($taskConfiguration)) {
                throw CircularProcessException::create($this->getCode(), $taskConfiguration->getCode());
            }
        }
    }

    /**
     * Cross all relations of a task to find all dependencies, and append them to the given array.
     */
    protected function buildDependencies(TaskConfiguration $taskConfig, array &$dependencies = []): array
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
     * Sort the tasks by dependencies.
     */
    protected function sortDependencies(array $dependencies): array
    {
        if (\count($dependencies) <= 1) {
            return $dependencies;
        }

        try {
            $this->checkCircularDependencies();
        } catch (CircularProcessException) {
            // Skipping the sort phase, it will throw later, on runtime
            return $dependencies;
        }

        $midOffset = round(\count($dependencies) / 2);
        $midTaskCode = $dependencies[(int)$midOffset];
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
