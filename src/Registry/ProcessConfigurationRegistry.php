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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Exception\LogicException;
use CleverAge\ProcessBundle\Exception\MissingProcessException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Build and holds all the process configurations.
 *
 * @final since 5.4
 */
class ProcessConfigurationRegistry implements ProcessConfigurationRegistryInterface
{
    /**
     * @var array<string, ProcessConfiguration>
     */
    protected array $processConfigurations = [];

    /**
     * @var array<string, string>|null
     */
    private ?array $deprecatedProcessCodes = null;

    /**
     * @param array<string, array<string, mixed>>                  $rawConfiguration
     * @param array{success_level?: string, failed_level?: string} $defaultLogLevels
     */
    public function __construct(
        protected array $rawConfiguration,
        protected string $defaultErrorStrategy,
        protected array $defaultLogLevels = [],
        protected ?LoggerInterface $logger = null,
    ) {
    }

    public function getProcessConfiguration(string $processCode): ProcessConfiguration
    {
        if (!$this->hasProcessConfiguration($processCode)) {
            throw MissingProcessException::create($processCode, $this->getProcessCodes());
        }
        $processCode = $this->resolveDeprecatedProcessCode($processCode);
        $this->resolveConfiguration($processCode);

        return $this->processConfigurations[$processCode];
    }

    /**
     * The processes whose configuration is not valid are left out (with an error log): they do not prevent listing the
     * other ones, getProcessConfiguration() throws when such a process is requested (e.g. to be executed).
     *
     * @return array<string, ProcessConfiguration>
     */
    public function getProcessConfigurations(): array
    {
        $processConfigurations = [];
        foreach (array_keys($this->rawConfiguration) as $processCode) {
            try {
                $this->resolveConfiguration($processCode);
            } catch (InvalidProcessConfigurationException|LogicException $exception) {
                $this->logger?->error(
                    "The process {$processCode} is left out of the process list, its configuration is not valid: {$exception->getMessage()}",
                    ['process_code' => $processCode, 'exception' => $exception]
                );

                continue;
            }
            $processConfigurations[$processCode] = $this->processConfigurations[$processCode];
        }

        return $processConfigurations;
    }

    /**
     * Forgets the resolved configurations, with the task instances and states of their last execution, e.g. between
     * two messages of a long-running worker: they are resolved again when needed.
     */
    public function reset(): void
    {
        $this->processConfigurations = [];
    }

    /**
     * The codes of the processes, without resolving their configuration.
     *
     * @return list<string>
     */
    public function getProcessCodes(): array
    {
        return array_map(strval(...), array_keys($this->rawConfiguration));
    }

    public function hasProcessConfiguration(string $processCode): bool
    {
        return \array_key_exists($processCode, $this->rawConfiguration)
            || \array_key_exists($processCode, $this->getDeprecatedProcessCodes());
    }

    /**
     * @return array<string, string> former name of the processes whose code has a dash => code
     */
    private function getDeprecatedProcessCodes(): array
    {
        return $this->deprecatedProcessCodes ??= DeprecatedCodeAliases::create(array_keys($this->rawConfiguration));
    }

    private function resolveDeprecatedProcessCode(string $processCode): string
    {
        if (\array_key_exists($processCode, $this->rawConfiguration)) {
            return $processCode;
        }
        $code = $this->getDeprecatedProcessCodes()[$processCode];
        // @deprecated since 5.7, the former name will not be found in v6.0
        trigger_deprecation(
            'cleverage/process-bundle',
            '5.7',
            'Using the process "%s" by the name "%s", with underscores instead of dashes, is deprecated: use its code "%s". The dashes of the codes were replaced by underscores before 5.7, the former name will not be found in v6.0.',
            $code,
            $processCode,
            $code,
        );

        return $code;
    }

    /**
     * Task references using the former name of a task whose code has a dash (deprecated).
     *
     * @param array<string, mixed> $rawProcessConfiguration
     *
     * @return array<string, mixed>
     */
    private function resolveDeprecatedTaskReferences(string $processCode, array $rawProcessConfiguration): array
    {
        /** @var array<string, mixed> $tasks */
        $tasks = $rawProcessConfiguration['tasks'];
        // Only when a reference is unknown (an unknown reference is reported by checkTaskReferences())
        $references = [$rawProcessConfiguration['entry_point'] ?? null, $rawProcessConfiguration['end_point'] ?? null];
        foreach ($tasks as $rawTaskConfiguration) {
            foreach (['outputs', 'error_outputs', 'errors'] as $key) {
                $references = [...$references, ...(\is_array($rawTaskConfiguration[$key] ?? null) ? $rawTaskConfiguration[$key] : [])];
            }
        }
        $unknownReferences = array_filter(
            $references,
            static fn (mixed $reference): bool => \is_string($reference) && !\array_key_exists($reference, $tasks)
        );
        if ([] === $unknownReferences) {
            return $rawProcessConfiguration;
        }
        $aliases = DeprecatedCodeAliases::create(array_keys($tasks));
        $resolve = static function (string $reference, string $key) use ($aliases, $processCode): string {
            if (!\array_key_exists($reference, $aliases)) {
                return $reference;
            }
            // @deprecated since 5.7, the former name will not be found in v6.0
            trigger_deprecation(
                'cleverage/process-bundle',
                '5.7',
                'Referencing the task "%s" of the process "%s" by the name "%s" in "%s", with underscores instead of dashes, is deprecated: use its code "%s". The dashes of the codes were replaced by underscores before 5.7, the former name will not be found in v6.0.',
                $aliases[$reference],
                $processCode,
                $reference,
                $key,
                $aliases[$reference],
            );

            return $aliases[$reference];
        };
        foreach (['entry_point', 'end_point'] as $key) {
            if (\is_string($rawProcessConfiguration[$key] ?? null)) {
                $rawProcessConfiguration[$key] = $resolve($rawProcessConfiguration[$key], $key);
            }
        }
        foreach ($tasks as $taskCode => $rawTaskConfiguration) {
            if (!\is_array($rawTaskConfiguration)) {
                continue;
            }
            foreach (['outputs', 'error_outputs', 'errors'] as $key) {
                if (\is_array($rawTaskConfiguration[$key] ?? null)) {
                    $rawTaskConfiguration[$key] = array_map(
                        static fn (mixed $reference): mixed => \is_string($reference) ? $resolve($reference, $key) : $reference,
                        $rawTaskConfiguration[$key]
                    );
                }
            }
            $tasks[$taskCode] = $rawTaskConfiguration;
        }
        $rawProcessConfiguration['tasks'] = $tasks;

        return $rawProcessConfiguration;
    }

    protected function resolveConfiguration(string $processCode): void
    {
        if (\array_key_exists($processCode, $this->processConfigurations)) {
            return;
        }
        $rawProcessConfiguration = $this->resolveDeprecatedTaskReferences($processCode, $this->rawConfiguration[$processCode]);
        /** @var TaskConfiguration[] $taskConfigurations */
        $taskConfigurations = [];
        /** @var array<string, string> $errorOutputsKeys */
        $errorOutputsKeys = [];
        foreach ($rawProcessConfiguration['tasks'] as $taskCode => $rawTaskConfiguration) {
            if ((is_countable($rawTaskConfiguration['errors']) ? \count($rawTaskConfiguration['errors']) : 0) > 0) {
                if ((is_countable($rawTaskConfiguration['error_outputs']) ? \count(
                    $rawTaskConfiguration['error_outputs']
                ) : 0) > 0) {
                    $m = "Don't define both 'errors' and 'error_outputs' for task {$taskCode}, these options ";
                    $m .= "are the same, 'errors' is deprecated, just use the new one 'error_outputs'";
                    throw new LogicException($m);
                }
                $rawTaskConfiguration['error_outputs'] = $rawTaskConfiguration['errors'];
                $errorOutputsKeys[$taskCode] = 'errors';
            }
            if (!\is_string($rawTaskConfiguration['service'] ?? null)) {
                // The "task" codes are resolved into services when the container is compiled (TaskLocatorCompilerPass)
                throw new LogicException("The task {$taskCode} of the process {$processCode} has no service: a \"task\" code can only be used in the configurations known when the container is compiled");
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
            $rawProcessConfiguration['public'],
            $rawProcessConfiguration['logs']['success_level'] ?? $this->defaultLogLevels['success_level'] ?? LogLevel::INFO,
            $rawProcessConfiguration['logs']['failed_level'] ?? $this->defaultLogLevels['failed_level'] ?? LogLevel::DEBUG,
            (bool) ($rawProcessConfiguration['exclusive'] ?? false)
        );

        // Check the task references, the links below would fail with a message naming neither the process nor the task
        $this->checkTaskReferences($processCode, $rawProcessConfiguration, $taskConfigurations, $errorOutputsKeys);
        $this->checkDuplicateTaskReferences($processCode, $taskConfigurations, $errorOutputsKeys);

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

        // Un-mark non-error branch (may be important for task that are in both branches): the tasks reached from a root
        // through outputs (markErrorBranch($root, false) stopped at the root, which is never marked)
        $visited = [];
        foreach ($processConfig->getMainTaskGroup() as $taskCode) {
            $task = $taskConfigurations[$taskCode];
            if ($task->isRoot()) {
                $this->unmarkMainBranch($task, $visited);
            }
        }

        // #106 - entry point should not have an ancestor
        if ($processConfig->getEntryPoint() instanceof TaskConfiguration && $processConfig->getEntryPoint()->getPreviousTasksConfigurations()) {
            throw InvalidProcessConfigurationException::createEntryPointHasAncestors($processConfig, $processConfig->getEntryPoint());
        }

        $this->processConfigurations[$processCode] = $processConfig;
    }

    /**
     * @param array<string, mixed>  $rawProcessConfiguration
     * @param TaskConfiguration[]   $taskConfigurations      indexed by task code
     * @param array<string, string> $errorOutputsKeys        "errors" for the tasks using this deprecated key
     */
    protected function checkTaskReferences(
        string $processCode,
        array $rawProcessConfiguration,
        array $taskConfigurations,
        array $errorOutputsKeys,
    ): void {
        foreach ($taskConfigurations as $taskCode => $taskConfig) {
            foreach ($taskConfig->getOutputs() as $nextTaskCode) {
                if (!\array_key_exists($nextTaskCode, $taskConfigurations)) {
                    throw InvalidProcessConfigurationException::createUnknownTaskReference($processCode, 'outputs', $nextTaskCode, $taskCode);
                }
            }
            foreach ($taskConfig->getErrorOutputs() as $errorTaskCode) {
                if (!\array_key_exists($errorTaskCode, $taskConfigurations)) {
                    throw InvalidProcessConfigurationException::createUnknownTaskReference($processCode, $errorOutputsKeys[$taskCode] ?? 'error_outputs', $errorTaskCode, $taskCode);
                }
            }
        }
        foreach (['entry_point', 'end_point'] as $key) {
            $referencedTaskCode = $rawProcessConfiguration[$key] ?? null;
            if (\is_string($referencedTaskCode) && !\array_key_exists($referencedTaskCode, $taskConfigurations)) {
                throw InvalidProcessConfigurationException::createUnknownTaskReference($processCode, $key, $referencedTaskCode);
            }
        }
    }

    /**
     * A task listed several times in the outputs of a task is executed once per occurrence (deprecated).
     *
     * @param TaskConfiguration[]   $taskConfigurations indexed by task code
     * @param array<string, string> $errorOutputsKeys   "errors" for the tasks using this deprecated key
     */
    private function checkDuplicateTaskReferences(string $processCode, array $taskConfigurations, array $errorOutputsKeys): void
    {
        foreach ($taskConfigurations as $taskCode => $taskConfig) {
            $references = [
                'outputs' => $taskConfig->getOutputs(),
                $errorOutputsKeys[$taskCode] ?? 'error_outputs' => $taskConfig->getErrorOutputs(),
            ];
            foreach ($references as $key => $codes) {
                foreach (array_unique(array_diff_assoc($codes, array_unique($codes))) as $duplicateCode) {
                    // @deprecated since 5.7, an exception will be thrown in v6.0
                    trigger_deprecation(
                        'cleverage/process-bundle',
                        '5.7',
                        'Listing the task "%s" several times in the "%s" of the task "%s" of the process "%s" is deprecated: it is executed once per occurrence. In v6.0, an exception will be thrown.',
                        $duplicateCode,
                        $key,
                        $taskCode,
                        $processCode,
                    );
                }
            }
        }
    }

    /**
     * @param array<string, true> $visited
     */
    private function unmarkMainBranch(TaskConfiguration $taskConfig, array &$visited): void
    {
        if (isset($visited[$taskConfig->getCode()])) {
            return;
        }
        $visited[$taskConfig->getCode()] = true;
        $taskConfig->setInErrorBranch(false);
        foreach ($taskConfig->getNextTasksConfigurations() as $nextTaskConfig) {
            $this->unmarkMainBranch($nextTaskConfig, $visited);
        }
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
