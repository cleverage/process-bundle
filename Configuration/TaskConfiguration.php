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
    protected $inputs = [];

    /**
     * @param string $code
     * @param string $serviceReference
     * @param array  $options
     * @param array  $inputs
     */
    public function __construct($code, $serviceReference, array $options, array $inputs)
    {
        $this->code = $code;
        $this->serviceReference = $serviceReference;
        $this->options = $options;
        $this->inputs = $inputs;
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
    public function getInputs(): array
    {
        return $this->inputs;
    }
}
