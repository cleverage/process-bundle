<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Registry;

use function array_key_exists;
use function array_keys;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\MissingProcessException;
use Psr\Log\LogLevel;

/**
 * Build and holds all the process configurations
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessConfigurationRegistry
{
    /** @var array */
    protected $rawConfiguration;

    /** @var string */
    protected $defaultErrorStrategy;

    /** @var ProcessConfiguration[] */
    protected $processConfigurations = [];

    /**
     * @param array  $rawConfiguration
     * @param string $defaultErrorStrategy
     */
    public function __construct(array $rawConfiguration, string $defaultErrorStrategy)
    {
        $this->rawConfiguration = $rawConfiguration;
        $this->defaultErrorStrategy = $defaultErrorStrategy;
    }

    /**
     * @param string $processCode
     *
     * @throws MissingProcessException
     *
     * @return ProcessConfiguration
     */
    public function getProcessConfiguration(string $processCode): ProcessConfiguration
    {
        if (!$this->hasProcessConfiguration($processCode)) {
            throw new MissingProcessException($processCode);
        }
        $this->resolveConfiguration($processCode);

        return $this->processConfigurations[$processCode];
    }

    /**
     * @return ProcessConfiguration[]
     */
    public function getProcessConfigurations(): array
    {
        foreach (array_keys($this->rawConfiguration) as $processCode) {
            $this->resolveConfiguration($processCode);
        }

        return $this->processConfigurations;
    }

    /**
     * @param string $processCode
     *
     * @return bool
     */
    public function hasProcessConfiguration(string $processCode): bool
    {
        return array_key_exists($processCode, $this->rawConfiguration);
    }

    /**
     * @param string $processCode
     */
    protected function resolveConfiguration(string $processCode): void
    {
        if (array_key_exists($processCode, $this->processConfigurations)) {
            return;
        }
        $rawProcessConfiguration = $this->rawConfiguration[$processCode];
        /** @var TaskConfiguration[] $taskConfigurations */
        $taskConfigurations = [];
        /** @noinspection ForeachSourceInspection */
        foreach ($rawProcessConfiguration['tasks'] as $taskCode => $rawTaskConfiguration) {
            if (\count($rawTaskConfiguration['errors']) > 0) {
                if (\count($rawTaskConfiguration['error_outputs']) > 0) {
                    $m = "Don't define both 'errors' and 'error_outputs' for task {$taskCode}, these options ";
                    $m .= "are the same, 'errors' is deprecated, just use the new one 'error_outputs'";
                    throw new \LogicException($m);
                }
                $rawTaskConfiguration['error_outputs'] = $rawTaskConfiguration['errors'];
            }
            $taskConfigurations[$taskCode] = new TaskConfiguration(
                $taskCode,
                $rawTaskConfiguration['service'],
                $rawTaskConfiguration['options'],
                $rawTaskConfiguration['description'],
                $rawTaskConfiguration['help'],
                $rawTaskConfiguration['outputs'],
                $rawTaskConfiguration['error_outputs'],
                $rawTaskConfiguration['error_strategy'] ?? $this->defaultErrorStrategy,
                $rawTaskConfiguration['log_errors'] ? $rawTaskConfiguration['log_level'] : LogLevel::DEBUG
            );
        }

        $processConfig = new ProcessConfiguration(
            $processCode,
            $taskConfigurations,
            $rawProcessConfiguration['options'],
            $rawProcessConfiguration['entry_point'],
            $rawProcessConfiguration['end_point'],
            $rawProcessConfiguration['description'],
            $rawProcessConfiguration['help'],
            $rawProcessConfiguration['public']
        );

        // Set links between tasks
        foreach ($taskConfigurations as $taskConfig) {
            foreach ($taskConfig->getOutputs() as $nextTaskCode) {
                $nextTaskConfig = $processConfig->getTaskConfiguration($nextTaskCode);
                $taskConfig->addNextTaskConfiguration($nextTaskConfig);
                $nextTaskConfig->addPreviousTaskConfiguration($taskConfig);
            }

            foreach ($taskConfig->getErrorOutputs() as $errorTaskCode) {
                $errorTaskConfig = $processConfig->getTaskConfiguration($errorTaskCode);
                $taskConfig->addErrorTaskConfiguration($errorTaskConfig);
                $errorTaskConfig->addPreviousTaskConfiguration($taskConfig);
            }
        }

        // Mark error branches
        foreach ($taskConfigurations as $taskConfig) {
            foreach ($taskConfig->getErrorTasksConfigurations() as $errorTaskConfig) {
                $this->markErrorBranch($errorTaskConfig);
            }
        }

        // Un-mark non-error branch (may be important for task that are in both branches)
        foreach ($processConfig->getMainTaskGroup() as $taskCode) {
            $task = $taskConfigurations[$taskCode];
            if ($task->isRoot()) {
                $this->markErrorBranch($task, false);
            }
        }

        $this->processConfigurations[$processCode] = $processConfig;
    }

    /**
     * @param TaskConfiguration $taskConfig
     * @param bool              $isErrorBranch
     */
    protected function markErrorBranch(TaskConfiguration $taskConfig, $isErrorBranch = true): void
    {
        if ($taskConfig->isInErrorBranch() !== $isErrorBranch) {
            $taskConfig->setInErrorBranch($isErrorBranch);
            foreach ($taskConfig->getNextTasksConfigurations() as $nextTasksConfig) {
                $this->markErrorBranch($nextTasksConfig, $isErrorBranch);
            }
        }
    }
}
