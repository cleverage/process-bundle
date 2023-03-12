<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Configuration;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Psr\Log\LogLevel;

/**
 * Represents a task configuration inside a process
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TaskConfiguration
{
    final public const STRATEGY_SKIP = 'skip';
    final public const STRATEGY_STOP = 'stop';

    /** @var TaskInterface */
    protected $task;

    /** @var array */
    protected $options = [];

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $help = '';

    /** @var array */
    protected $outputs = [];

    /** @var array */
    protected $errorOutputs = [];

    /** @var ProcessState */
    protected $state;

    /** @var TaskConfiguration[] */
    protected $nextTasksConfigurations = [];

    /** @var TaskConfiguration[] */
    protected $previousTasksConfigurations = [];

    /** @var TaskConfiguration[] */
    protected $errorTasksConfigurations = [];

    /** @var bool */
    protected $inErrorBranch = false;

    /** @var string */
    protected $errorStrategy;

    /** @var string */
    protected $logLevel;

    /** @var bool */
    protected $logErrors;

    /**
     * @param string $code
     * @param string $serviceReference
     */
    public function __construct(
        protected $code,
        protected $serviceReference,
        array $options,
        string $description = '',
        string $help = '',
        array $outputs = [],
        array $errorOutputs = [],
        string $errorStrategy = self::STRATEGY_SKIP,
        string $logLevel = LogLevel::CRITICAL
    ) {
        $this->options = $options;
        $this->description = $description;
        $this->help = $help;
        $this->outputs = $outputs;
        $this->errorOutputs = $errorOutputs;
        $this->errorStrategy = $errorStrategy;
        $this->logLevel = $logLevel;
        $this->logErrors = $logLevel !== LogLevel::DEBUG; // @deprecated, remove me in next version
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getServiceReference(): string
    {
        return $this->serviceReference;
    }

    /**
     * @return TaskInterface
     */
    public function getTask(): ?TaskInterface
    {
        return $this->task;
    }

    public function setTask(TaskInterface $task)
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

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function getOption($code, mixed $default = null)
    {
        if (array_key_exists($code, $this->options)) {
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
     *
     */
    public function getErrors(): array
    {
        @trigger_error('Deprecated method, use getErrorOutputs instead', E_USER_DEPRECATED);

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

    public function setState(ProcessState $state)
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

    public function addNextTaskConfiguration(TaskConfiguration $nextTaskConfiguration)
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

    public function addPreviousTaskConfiguration(TaskConfiguration $previousTaskConfiguration)
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

    public function addErrorTaskConfiguration(TaskConfiguration $errorTaskConfiguration)
    {
        $this->errorTasksConfigurations[] = $errorTaskConfiguration;
    }

    public function isInErrorBranch(): bool
    {
        return $this->inErrorBranch;
    }

    public function setInErrorBranch(bool $inErrorBranch)
    {
        $this->inErrorBranch = $inErrorBranch;
    }

    public function isRoot(): bool
    {
        return empty($this->getPreviousTasksConfigurations()) && !$this->isInErrorBranch();
    }

    /**
     * Check task ancestors to find if it have a given task as parent
     *
     *
     * @return bool
     */
    public function hasAncestor(TaskConfiguration $taskConfig)
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
     * Check task ancestors to find if it have a given task as child
     *
     * @param bool              $checkErrors
     * @return bool
     */
    public function hasDescendant(TaskConfiguration $taskConfig, $checkErrors = true)
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

    /**
     * @deprecated Use getLogLevel instead
     *
     */
    public function isLogErrors(): bool
    {
        @trigger_error('Deprecated method, use getLogLevel instead', E_USER_DEPRECATED);

        return $this->logErrors;
    }
}
