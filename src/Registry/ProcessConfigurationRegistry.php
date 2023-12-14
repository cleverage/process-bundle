<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Exception\MissingProcessException;
use Psr\Log\LogLevel;

/**
 * Build and holds all the process configurations.
 */
class ProcessConfigurationRegistry
{
    /**
     * @var ProcessConfiguration[]
     */
    protected array $processConfigurations = [];

    public function __construct(
        protected array $rawConfiguration,
        protected string $defaultErrorStrategy
    ) {
    }

    public function getProcessConfiguration(string $processCode): ProcessConfiguration
    {
        if (!$this->hasProcessConfiguration($processCode)) {
            throw MissingProcessException::create($processCode);
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

    public function hasProcessConfiguration(string $processCode): bool
    {
        return \array_key_exists($processCode, $this->rawConfiguration);
    }

    protected function resolveConfiguration(string $processCode): void
    {
        if (\array_key_exists($processCode, $this->processConfigurations)) {
            return;
        }
        $rawProcessConfiguration = $this->rawConfiguration[$processCode];
        /** @var TaskConfiguration[] $taskConfigurations */
        $taskConfigurations = [];
        foreach ($rawProcessConfiguration['tasks'] as $taskCode => $rawTaskConfiguration) {
            if ((is_countable($rawTaskConfiguration['errors']) ? \count($rawTaskConfiguration['errors']) : 0) > 0) {
                if ((is_countable($rawTaskConfiguration['error_outputs']) ? \count(
                    $rawTaskConfiguration['error_outputs']
                ) : 0) > 0) {
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
                $rawTaskConfiguration['log_level'] ?: LogLevel::DEBUG
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

        // #106 - entry point should not have an ancestor
        if ($processConfig->getEntryPoint() && $processConfig->getEntryPoint()->getPreviousTasksConfigurations()) {
            throw InvalidProcessConfigurationException::createEntryPointHasAncestors($processConfig, $processConfig->getEntryPoint());
        }

        $this->processConfigurations[$processCode] = $processConfig;
    }

    protected function markErrorBranch(TaskConfiguration $taskConfig, bool $isErrorBranch = true): void
    {
        if ($taskConfig->isInErrorBranch() !== $isErrorBranch) {
            $taskConfig->setInErrorBranch($isErrorBranch);
            foreach ($taskConfig->getNextTasksConfigurations() as $nextTasksConfig) {
                $this->markErrorBranch($nextTasksConfig, $isErrorBranch);
            }
        }
    }
}
