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

namespace CleverAge\ProcessBundle\DependencyInjection;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @see https://symfony.com/doc/current/bundles/configuration.html
 *
 * @internal
 */
class Configuration implements ConfigurationInterface
{
    protected const LOG_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    public function __construct(
        protected string $root = 'clever_age_process',
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->root);
        $rootNode = $treeBuilder->getRootNode();
        $definition = $rootNode->children();
        // Default error strategy
        $definition->enumNode('default_error_strategy')
            ->values([TaskConfiguration::STRATEGY_SKIP, TaskConfiguration::STRATEGY_STOP])
            ->defaultValue(TaskConfiguration::STRATEGY_STOP)
            ->info('Error strategy of the tasks without "error_strategy": "skip" the item or "stop" the process');

        // Default log levels of the end of the processes
        $logsDefinition = $definition->arrayNode('logs')
            ->addDefaultsIfNotSet()
            ->children();
        $logsDefinition->enumNode('success_level')
            ->values(self::LOG_LEVELS)
            ->defaultValue(LogLevel::INFO)
            ->info('Level of the "Process <code> succeed" log');
        $logsDefinition->enumNode('failed_level')
            ->values(self::LOG_LEVELS)
            ->defaultValue(LogLevel::DEBUG)
            ->info('Level of the "Process <code> failed" log');

        $this->appendRootProcessConfigDefinition($definition);
        $this->appendRootTransformersConfigDefinition($definition);

        return $treeBuilder;
    }

    /**
     * "generic_transformers" root configuration.
     */
    protected function appendRootTransformersConfigDefinition(NodeBuilder $definition): void
    {
        $transformersArrayDefinition = $definition->arrayNode('generic_transformers')
            ->useAttributeAsKey('code')
            // Keep the dashes of the codes (replaced by underscores before 5.7, see DeprecatedCodeAliases)
            ->normalizeKeys(false)
            ->arrayPrototype();

        // Process list
        $transformerListDefinition = $transformersArrayDefinition
            ->performNoDeepMerging()
            ->cannotBeOverwritten()
            ->info('Unique custom transformer code, usable as a transformer in the processes')
            ->children();

        $this->appendTransformerConfigDefinition($transformerListDefinition);
    }

    /**
     * Single transformer configuration.
     */
    protected function appendTransformerConfigDefinition(NodeBuilder $definition): void
    {
        $definition
            ->arrayNode('contextual_options')
            ->info('Options of the transformer (code: definition with required, default, default_is_null), injected in the "transformers" with "{{ code }}"')
            ->example(['delimiter' => ['default' => ',']])
            // Keep the dashes of the option codes, as written in the options given to the transformer and in "{{ code }}"
            // (their former name, with underscores, is deprecated, see GenericTransformer)
            ->normalizeKeys(false)
            ->variablePrototype()
            ->end()
            ->end()
            ->arrayNode('transformers')
            ->info('Transformers applied in sequence, by code, with their options')
            ->example(['explode' => ['delimiter' => '{{ separator }}']])
            // Keep the dashes of the transformer codes (the former name of a generic transformer is deprecated, as the
            // former name of a transformer code that is only registered with underscores, see GenericTransformer)
            ->normalizeKeys(false)
            ->variablePrototype()
            ->end()
            ->end();
    }

    /**
     * "configurations" root configuration.
     */
    protected function appendRootProcessConfigDefinition(NodeBuilder $definition): void
    {
        $configurationsArrayDefinition = $definition->arrayNode('configurations')
            ->useAttributeAsKey('code')
            // Keep the dashes of the codes (replaced by underscores before 5.7, see DeprecatedCodeAliases)
            ->normalizeKeys(false)
            ->arrayPrototype();

        // Process list
        $processListDefinition = $configurationsArrayDefinition
            ->performNoDeepMerging()
            ->info('Unique custom process code')
            ->cannotBeOverwritten()
            ->children();

        $this->appendProcessConfigDefinition($processListDefinition);
    }

    protected function appendProcessConfigDefinition(NodeBuilder $definition): void
    {
        $definition
            ->scalarNode('entry_point')
            ->info('Code of the task receiving the input of the process (the input is ignored if not set)')
            ->defaultNull()
            ->end()
            ->scalarNode('end_point')
            ->info('Code of the task whose last output is returned by the process (null if not set)')
            ->defaultNull()
            ->end()
            ->scalarNode('description')
            ->info('Description of the process, shown by the cleverage:process:list command')
            ->defaultValue('')
            ->end()
            ->scalarNode('help')
            ->info('Help of the process, shown by the cleverage:process:help command')
            ->defaultValue('')
            ->end()
            ->booleanNode('public')
            ->info('Whether the process is listed by the cleverage:process:list command')
            ->defaultTrue()
            ->end()
            ->booleanNode('exclusive')
            ->info('Whether an execution of the process fails when another one is running (lock of symfony/lock)')
            ->defaultFalse()
            ->validate()
            ->ifTrue(static fn (bool $exclusive): bool => $exclusive && !class_exists(LockFactory::class))
            ->thenInvalid('An exclusive process requires the symfony/lock component: composer require symfony/lock')
            ->end()
            ->end()
            ->arrayNode('options')
            ->info('Free options of the process, read by other bundles (e.g. "ui" for the CleverAge UI process bundle)')
            ->variablePrototype()
            ->end()
            ->end()
            ->arrayNode('logs')
            ->info('Override the default log levels of the end of the process')
            ->addDefaultsIfNotSet()
            ->children()
            ->enumNode('success_level')
            ->values([...self::LOG_LEVELS, null])
            ->defaultNull()
            ->end()
            ->enumNode('failed_level')
            ->values([...self::LOG_LEVELS, null])
            ->defaultNull()
            ->end()
            ->end()
            ->end();

        $tasksArrayDefinition = $definition
            ->arrayNode('tasks')
            ->useAttributeAsKey('code')
            // Keep the dashes of the codes (replaced by underscores before 5.7, see DeprecatedCodeAliases)
            ->normalizeKeys(false)
            ->arrayPrototype();

        // Process list
        $taskListDefinition = $tasksArrayDefinition
            ->performNoDeepMerging()
            ->cannotBeOverwritten()
            ->info('Unique custom task code')
            ->beforeNormalization()
            // e.g. "outputs: [next]" indented as a task: the list was reported as an unknown option "0"
            ->ifTrue(static fn (mixed $task): bool => \is_array($task) && [] !== $task && array_is_list($task))
            ->thenInvalid('A task is defined by a mapping ("task", "options", "outputs"...), not by a list: check the indentation of the "outputs" or "error_outputs" of the previous task. Given: %s')
            ->end()
            ->validate()
            ->ifTrue(static fn (array $task): bool => null !== $task['service'] && null !== $task['task'])
            ->thenInvalid('A task must define either its code ("task: <code>") or, deprecated, its "service", not both: %s')
            ->end()
            ->validate()
            ->ifTrue(static fn (array $task): bool => null === $task['service'] && null === $task['task'])
            ->thenInvalid('A task must define its code ("task: <code>", given on the reference page of each task), it defines neither "task" nor, deprecated, "service": %s')
            ->end()
            ->children();

        $this->appendTaskConfigDefinition($taskListDefinition);
    }

    protected function appendTaskConfigDefinition(NodeBuilder $definition): void
    {
        $definition->scalarNode('service')
            ->info('Deprecated, use "task": service id or alias of the task, with or without a leading "@"')
            ->setDeprecated(
                'cleverage/process-bundle',
                '5.4',
                'The "%node%" option of the task "%path%" is deprecated, reference the task by its code instead ("task: <code>", given on the reference page of each task; give a code to your own tasks with #[AsTask] or a "cleverage.task" tag). It will be removed in v6.0.'
            )
            ->defaultNull();
        $definition->scalarNode('task')
            ->info('Code of the task (cleverage.task tag, #[AsTask])')
            ->defaultNull();
        $definition->scalarNode('description')
            ->info('Description of the task, shown by the cleverage:process:help command')
            ->defaultValue('');
        $definition->scalarNode('help')
            ->info('Help of the task, shown by the cleverage:process:help command')
            ->defaultValue('');
        $definition->arrayNode('options')
            ->info('Options of the task, given on the reference page of each task')
            ->variablePrototype()
            ->end();
        $definition->scalarNode('error_strategy')
            ->info('On error, "skip" the item or "stop" the process; "default_error_strategy" if not set')
            ->defaultNull();
        $definition->enumNode('log_level')
            ->values(self::LOG_LEVELS)
            ->info('Level of the log of the errors of the task')
            ->defaultValue(LogLevel::CRITICAL);

        foreach (['outputs', 'errors', 'error_outputs'] as $nodeName) {
            $node = $definition->arrayNode($nodeName);
            if ('outputs' === $nodeName) {
                $node->info('Codes of the tasks receiving the output of the task')
                    ->example(['next_task', 'other_task']);
            } elseif ('error_outputs' === $nodeName) {
                $node->info('Codes of the tasks receiving the error output of the task (by default, its input)')
                    ->example(['error_task']);
            } elseif ('errors' === $nodeName) {
                $node->info('Deprecated, use "error_outputs"')
                    ->setDeprecated(
                        'cleverage/process-bundle',
                        '5.4',
                        'The "%node%" option of the task "%path%" is deprecated, use "error_outputs" instead. It will be removed in v6.0.'
                    );
            }
            $node->beforeNormalization()
                ->ifString()
                ->then(static fn ($item): array => [$item])->end()
                ->scalarPrototype();
        }
    }
}
