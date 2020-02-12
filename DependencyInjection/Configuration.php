<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
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
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class Configuration implements ConfigurationInterface
{
    /** @var string */
    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root = 'clever_age_process')
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);
        $definition = $rootNode->children();

        // Default error strategy
        $definition->enumNode('default_error_strategy')
            ->values([
                TaskConfiguration::STRATEGY_SKIP,
                TaskConfiguration::STRATEGY_STOP,
            ])
            ->isRequired();

        $this->appendRootProcessConfigDefinition($definition);
        $this->appendRootTransformersConfigDefinition($definition);

        $definition->end();

        return $treeBuilder;
    }

    /**
     * "generic_transformers" root configuration
     *
     * @param NodeBuilder $definition
     */
    protected function appendRootTransformersConfigDefinition(NodeBuilder $definition)
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

        $transformerListDefinition->end();
        $transformersArrayDefinition->end();
    }

    /**
     * Single transformer configuration
     *
     * @param NodeBuilder $definition
     */
    protected function appendTransformerConfigDefinition(NodeBuilder $definition)
    {
        $definition
            ->arrayNode('contextual_options')->prototype('variable')->end()->end()
            ->arrayNode('transformers')->prototype('variable')->end()->end();
    }

    /**
     * "configurations" root configuration
     * @TODO rename this root as "processes"
     *
     * @param NodeBuilder $definition
     */
    protected function appendRootProcessConfigDefinition(NodeBuilder $definition)
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

        $processListDefinition->end();
        $configurationsArrayDefinition->end();
    }

    /**
     * @param NodeBuilder $definition
     */
    protected function appendProcessConfigDefinition(NodeBuilder $definition)
    {
        $definition
            ->scalarNode('entry_point')->defaultNull()->end()
            ->scalarNode('end_point')->defaultNull()->end()
            ->scalarNode('description')->defaultValue('')->end()
            ->scalarNode('help')->defaultValue('')->end()
            ->scalarNode('public')->defaultTrue()->end()
            ->arrayNode('options')->prototype('variable')->end()->end();

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

        $taskListDefinition->end();
        $tasksArrayDefinition->end();
    }

    /**
     * @param NodeBuilder $definition
     */
    protected function appendTaskConfigDefinition(NodeBuilder $definition)
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

        $definition
            ->scalarNode('service')->isRequired()->end()
            ->scalarNode('description')->defaultValue('')->end()
            ->scalarNode('help')->defaultValue('')->end()
            ->arrayNode('options')->prototype('variable')->end()->end()
            ->scalarNode('error_strategy')->defaultNull()->end()
            ->enumNode('log_level')->values($logLevels)->defaultValue(LogLevel::CRITICAL)->end()
            ->booleanNode('log_errors')->defaultTrue()->setDeprecated()->end();

        foreach (['outputs', 'errors', 'error_outputs'] as $nodeName) {
            $definition->arrayNode($nodeName)
                ->beforeNormalization()
                ->ifString()->then(function ($item) {
                    return [$item];
                })->end()
                ->prototype('scalar')->end()->end();
        }
    }
}
