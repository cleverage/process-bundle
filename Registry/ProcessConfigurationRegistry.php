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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\MissingProcessException;

/**
 * Build and holds all the process configurations
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessConfigurationRegistry
{
    /** @var ProcessConfiguration[] */
    protected $processConfigurations = [];

    /**
     * @param array $rawConfiguration
     */
    public function __construct(array $rawConfiguration)
    {
        foreach ($rawConfiguration as $processCode => $rawProcessConfiguration) {
            $taskConfigurations = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($rawProcessConfiguration['tasks'] as $taskCode => $rawTaskConfiguration) {
                $taskConfigurations[$taskCode] = new TaskConfiguration(
                    $taskCode,
                    $rawTaskConfiguration['service'],
                    $rawTaskConfiguration['options'],
                    $rawTaskConfiguration['outputs'],
                    $rawTaskConfiguration['errors']
                );
            }

            $processConfig = new ProcessConfiguration(
                $processCode,
                $taskConfigurations,
                $rawProcessConfiguration['options'],
                $rawProcessConfiguration['entry_point'],
                $rawProcessConfiguration['end_point']
            );

            // Set links between tasks
            /** @var TaskConfiguration $taskConfig */
            foreach ($taskConfigurations as $taskConfig) {
                foreach ($taskConfig->getOutputs() as $nextTaskCode) {
                    $nextTaskConfig = $processConfig->getTaskConfiguration($nextTaskCode);
                    $taskConfig->addNextTaskConfiguration($nextTaskConfig);
                    $nextTaskConfig->addPreviousTaskConfiguration($taskConfig);
                }
                foreach ($taskConfig->getErrors() as $errorTaskCode) {
                    $errorTaskConfig = $processConfig->getTaskConfiguration($errorTaskCode);
                    $taskConfig->addErrorTaskConfiguration($errorTaskConfig);
                }
            }

            // Mark error branches
            foreach ($taskConfigurations as $taskConfig) {
                foreach ($taskConfig->getErrorTasksConfigurations() as $errorTaskConfig) {
                    $this->markErrorBranch($errorTaskConfig);
                }
            }

            $this->processConfigurations[$processCode] = $processConfig;
        }
    }

    /**
     * @param string $processCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingProcessException
     *
     * @return ProcessConfiguration
     */
    public function getProcessConfiguration(string $processCode): ProcessConfiguration
    {
        if (!$this->hasProcessConfiguration($processCode)) {
            throw new MissingProcessException($processCode);
        }

        return $this->processConfigurations[$processCode];
    }

    /**
     * @return ProcessConfiguration[]
     */
    public function getProcessConfigurations(): array
    {
        return $this->processConfigurations;
    }

    /**
     * @param string $processCode
     *
     * @return bool
     */
    public function hasProcessConfiguration(string $processCode): bool
    {
        return array_key_exists($processCode, $this->processConfigurations);
    }

    /**
     * @param TaskConfiguration $taskConfig
     */
    protected function markErrorBranch(TaskConfiguration $taskConfig)
    {
        $taskConfig->setInErrorBranch(true);
        foreach ($taskConfig->getNextTasksConfigurations() as $nextTasksConfig) {
            $this->markErrorBranch($nextTasksConfig);
        }
    }
}
