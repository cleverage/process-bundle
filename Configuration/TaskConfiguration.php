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

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Represents a task configuration inside a process
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TaskConfiguration
{
    public const STRATEGY_SKIP = 'skip';
    public const STRATEGY_STOP = 'stop';
    public const STRATEGY_CONTINUE = 'continue';

    /** @var string */
    protected $code;

    /** @var string */
    protected $serviceReference;

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
    protected $errors = [];

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

    /** @var bool */
    protected $logErrors;

    /**
     * @param string $code
     * @param string $serviceReference
     * @param array  $options
     * @param string $description
     * @param string $help
     * @param array  $outputs
     * @param array  $errors
     * @param string $errorStrategy
     * @param bool   $logErrors
     */
    public function __construct(
        $code,
        $serviceReference,
        array $options,
        string $description = '',
        string $help = '',
        array $outputs = [],
        array $errors = [],
        string $errorStrategy = self::STRATEGY_SKIP,
        bool $logErrors = true
    ) {
        $this->code = $code;
        $this->serviceReference = $serviceReference;
        $this->options = $options;
        $this->description = $description;
        $this->help = $help;
        $this->outputs = $outputs;
        $this->errors = $errors;
        $this->errorStrategy = $errorStrategy;
        $this->logErrors = $logErrors;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getServiceReference(): string
    {
        return $this->serviceReference;
    }

    /**
     * @return TaskInterface
     */
    public function getTask(): TaskInterface
    {
        return $this->task;
    }

    /**
     * @param TaskInterface $task
     */
    public function setTask(TaskInterface $task)
    {
        $this->task = $task;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $code
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($code, $default = null)
    {
        if (array_key_exists($code, $this->options)) {
            return $this->options[$code];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return ProcessState
     */
    public function getState(): ProcessState
    {
        return $this->state;
    }

    /**
     * @param ProcessState $state
     */
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

    /**
     * @param TaskConfiguration $nextTaskConfiguration
     */
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

    /**
     * @param TaskConfiguration $previousTaskConfiguration
     */
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

    /**
     * @param TaskConfiguration $errorTaskConfiguration
     */
    public function addErrorTaskConfiguration(TaskConfiguration $errorTaskConfiguration)
    {
        $this->errorTasksConfigurations[] = $errorTaskConfiguration;
    }

    /**
     * @return bool
     */
    public function isInErrorBranch(): bool
    {
        return $this->inErrorBranch;
    }

    /**
     * @param bool $inErrorBranch
     */
    public function setInErrorBranch(bool $inErrorBranch)
    {
        $this->inErrorBranch = $inErrorBranch;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return empty($this->getPreviousTasksConfigurations()) && !$this->isInErrorBranch();
    }

    /**
     * Check task ancestors to find if it have a given task as parent
     *
     * @param TaskConfiguration $taskConfig
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
     * @param TaskConfiguration $taskConfig
     * @param bool              $checkErrors
     *
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

    /**
     * @return string
     */
    public function getErrorStrategy(): string
    {
        return $this->errorStrategy;
    }

    /**
     * @return bool
     */
    public function isLogErrors(): bool
    {
        return $this->logErrors;
    }
}
