<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Configuration;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Psr\Log\LogLevel;

/**
 * Represents a task configuration inside a process.
 */
class TaskConfiguration
{
    final public const STRATEGY_SKIP = 'skip';

    final public const STRATEGY_STOP = 'stop';

    protected ?TaskInterface $task = null;

    protected ProcessState $state;

    /**
     * @var TaskConfiguration[]
     */
    protected array $nextTasksConfigurations = [];

    /**
     * @var TaskConfiguration[]
     */
    protected array $previousTasksConfigurations = [];

    /**
     * @var TaskConfiguration[]
     */
    protected array $errorTasksConfigurations = [];

    protected bool $inErrorBranch = false;

    protected bool $logErrors;

    public function __construct(
        protected string $code,
        protected string $serviceReference,
        protected array $options,
        protected string $description = '',
        protected string $help = '',
        protected array $outputs = [],
        protected array $errorOutputs = [],
        protected string $errorStrategy = self::STRATEGY_SKIP,
        protected string $logLevel = LogLevel::CRITICAL,
    ) {
        $this->logErrors = LogLevel::DEBUG !== $logLevel; // @deprecated, remove me in next version
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getServiceReference(): string
    {
        return $this->serviceReference;
    }

    public function getTask(): ?TaskInterface
    {
        return $this->task;
    }

    public function setTask(TaskInterface $task): void
    {
        $this->task = $task;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $code, mixed $default = null): mixed
    {
        if (\array_key_exists($code, $this->options)) {
            return $this->options[$code];
        }

        return $default;
    }

    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @deprecated Use getErrorOutputs method instead
     */
    public function getErrors(): array
    {
        @trigger_error('Deprecated method, use getErrorOutputs instead', \E_USER_DEPRECATED);

        return $this->getErrorOutputs();
    }

    public function getErrorOutputs(): array
    {
        return $this->errorOutputs;
    }

    public function getState(): ProcessState
    {
        return $this->state;
    }

    public function setState(ProcessState $state): void
    {
        $this->state = $state;
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getNextTasksConfigurations(): array
    {
        return $this->nextTasksConfigurations;
    }

    public function addNextTaskConfiguration(self $nextTaskConfiguration): void
    {
        $this->nextTasksConfigurations[] = $nextTaskConfiguration;
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getPreviousTasksConfigurations(): array
    {
        return $this->previousTasksConfigurations;
    }

    public function addPreviousTaskConfiguration(self $previousTaskConfiguration): void
    {
        $this->previousTasksConfigurations[] = $previousTaskConfiguration;
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getErrorTasksConfigurations(): array
    {
        return $this->errorTasksConfigurations;
    }

    public function addErrorTaskConfiguration(self $errorTaskConfiguration): void
    {
        $this->errorTasksConfigurations[] = $errorTaskConfiguration;
    }

    public function isInErrorBranch(): bool
    {
        return $this->inErrorBranch;
    }

    public function setInErrorBranch(bool $inErrorBranch): void
    {
        $this->inErrorBranch = $inErrorBranch;
    }

    public function isRoot(): bool
    {
        return [] === $this->getPreviousTasksConfigurations() && !$this->isInErrorBranch();
    }

    /**
     * Check task ancestors to find if it have a given task as parent.
     */
    public function hasAncestor(self $taskConfig): bool
    {
        foreach ($this->getPreviousTasksConfigurations() as $previousTaskConfig) {
            // Avoid errors for direct ancestors
            if ($this->getCode() === $previousTaskConfig->getCode()) {
                continue;
            }

            if ($previousTaskConfig->getCode() === $taskConfig->getCode()) {
                return true;
            }

            if ($previousTaskConfig->hasAncestor($taskConfig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check task ancestors to find if it have a given task as child.
     */
    public function hasDescendant(self $taskConfig, bool $checkErrors = true): bool
    {
        foreach ($this->getNextTasksConfigurations() as $nextTaskConfig) {
            // Avoid errors for direct descendant
            if ($this->getCode() === $nextTaskConfig->getCode()) {
                continue;
            }

            if ($nextTaskConfig->getCode() === $taskConfig->getCode()) {
                return true;
            }

            if ($nextTaskConfig->hasDescendant($taskConfig, $checkErrors)) {
                return true;
            }
        }

        if ($checkErrors) {
            foreach ($this->getErrorTasksConfigurations() as $errorTasksConfig) {
                // Avoid errors for direct error descendant
                if ($this->getCode() === $errorTasksConfig->getCode()) {
                    continue;
                }

                if ($errorTasksConfig->getCode() === $taskConfig->getCode()) {
                    return true;
                }

                if ($errorTasksConfig->hasDescendant($taskConfig, $checkErrors)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getErrorStrategy(): string
    {
        return $this->errorStrategy;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }
}
