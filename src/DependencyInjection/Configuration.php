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
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
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
            ->isRequired();

        $this->appendRootProcessConfigDefinition($definition);
        $this->appendRootTransformersConfigDefinition($definition);

        return $treeBuilder;
    }

    /**
     * "generic_transformers" root configuration.
     */
    protected function appendRootTransformersConfigDefinition(NodeBuilder $definition): void
    {
        /** @var ArrayNodeDefinition $transformersArrayDefinition */
        $transformersArrayDefinition = $definition->arrayNode('generic_transformers')
            ->useAttributeAsKey('code')
            ->prototype('array');

        // Process list
        $transformerListDefinition = $transformersArrayDefinition
            ->performNoDeepMerging()
            ->cannotBeOverwritten()
            ->info('Unique custom transformer code')
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
            ->prototype('variable')
            ->end()
            ->end()
            ->arrayNode('transformers')
            ->prototype('variable')
            ->end()
            ->end();
    }

    /**
     * "configurations" root configuration.
     */
    protected function appendRootProcessConfigDefinition(NodeBuilder $definition): void
    {
        /** @var ArrayNodeDefinition $configurationsArrayDefinition */
        $configurationsArrayDefinition = $definition->arrayNode('configurations')
            ->useAttributeAsKey('code')
            ->prototype('array');

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
            ->defaultNull()
            ->end()
            ->scalarNode('end_point')
            ->defaultNull()
            ->end()
            ->scalarNode('description')
            ->defaultValue('')
            ->end()
            ->scalarNode('help')
            ->defaultValue('')
            ->end()
            ->scalarNode('public')
            ->defaultTrue()
            ->end()
            ->arrayNode('options')
            ->prototype('variable')
            ->end()
            ->end();

        /** @var ArrayNodeDefinition $tasksArrayDefinition */
        $tasksArrayDefinition = $definition
            ->arrayNode('tasks')
            ->useAttributeAsKey('code')
            ->prototype('array');

        // Process list
        $taskListDefinition = $tasksArrayDefinition
            ->performNoDeepMerging()
            ->cannotBeOverwritten()
            ->info('Unique custom task code')
            ->children();

        $this->appendTaskConfigDefinition($taskListDefinition);
    }

    protected function appendTaskConfigDefinition(NodeBuilder $definition): void
    {
        $logLevels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];

        $definition->scalarNode('service')
            ->isRequired();
        $definition->scalarNode('description')
            ->defaultValue('');
        $definition->scalarNode('help')
            ->defaultValue('');
        $definition->arrayNode('options')
            ->prototype('variable')
            ->end();
        $definition->scalarNode('error_strategy')
            ->defaultNull();
        $definition->enumNode('log_level')
            ->values($logLevels)
            ->defaultValue(LogLevel::CRITICAL);

        foreach (['outputs', 'errors', 'error_outputs'] as $nodeName) {
            $definition->arrayNode($nodeName)
                ->beforeNormalization()
                ->ifString()
                ->then(fn ($item): array => [$item])->end()
                ->prototype('scalar');
        }
    }
}
