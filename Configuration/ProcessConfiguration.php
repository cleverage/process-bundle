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

    /**
     * @param string              $code
     * @param TaskConfiguration[] $taskConfigurations
     * @param array               $options
     * @param string              $entryPoint
     * @param string              $endPoint
     */
    public function __construct($code, array $taskConfigurations, array $options = [], $entryPoint = null, $endPoint = null)
    {
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
     * @return TaskConfiguration
     */
    public function getEntryPoint(): TaskConfiguration
    {
        if (null === $this->entryPoint) {
            return reset($this->taskConfigurations);
        }

        return $this->getTaskConfiguration($this->entryPoint);
    }

    /**
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration
     */
    public function getEndPoint(): TaskConfiguration
    {
        if (null === $this->endPoint) {
            return end($this->taskConfigurations);
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
     * @param TaskConfiguration $currentTaskConfiguration
     *
     * @deprecated
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration[]
     */
    public function getNextTasks(TaskConfiguration $currentTaskConfiguration)
    {
        return $currentTaskConfiguration->getNextTasksConfigurations();
    }

    /**
     * @param TaskConfiguration $currentTaskConfiguration
     *
     * @deprecated
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration[]
     */
    public function getErrorTasks(TaskConfiguration $currentTaskConfiguration)
    {
        return $currentTaskConfiguration->getErrorTasksConfigurations();
    }
}
