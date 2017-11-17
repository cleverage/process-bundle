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
    /** @var string */
    protected $code;

    /** @var string */
    protected $serviceReference;

    /** @var TaskInterface */
    protected $task;

    /** @var array */
    protected $options = [];

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

    /**
     * @param string $code
     * @param string $serviceReference
     * @param array  $options
     * @param array  $outputs
     * @param array  $errors
     */
    public function __construct($code, $serviceReference, array $options, array $outputs = [], array $errors = [])
    {
        $this->code = $code;
        $this->serviceReference = $serviceReference;
        $this->options = $options;
        $this->outputs = $outputs;
        $this->errors = $errors;
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
}
