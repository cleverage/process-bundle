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

    /** @var TaskConfiguration[] */
    protected $taskConfigurations;

    /**
     * @param string              $code
     * @param array               $options
     * @param string              $entryPoint
     * @param TaskConfiguration[] $taskConfigurations
     */
    public function __construct($code, array $taskConfigurations, array $options = [], $entryPoint = null)
    {
        $this->code = $code;
        $this->taskConfigurations = $taskConfigurations;
        $this->options = $options;
        $this->entryPoint = $entryPoint;
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
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration[]
     */
    public function getNextTasks(TaskConfiguration $currentTaskConfiguration)
    {
        $taskConfigurations = [];
        foreach ($currentTaskConfiguration->getOutputs() as $taskCode) {
            $taskConfigurations[] = $this->getTaskConfiguration($taskCode);
        }

        return $taskConfigurations;
    }

    /**
     * @param TaskConfiguration $currentTaskConfiguration
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration[]
     */
    public function getErrorTasks(TaskConfiguration $currentTaskConfiguration)
    {
        $taskConfigurations = [];
        foreach ($currentTaskConfiguration->getErrors() as $taskCode) {
            $taskConfigurations[] = $this->getTaskConfiguration($taskCode);
        }

        return $taskConfigurations;
    }
}
