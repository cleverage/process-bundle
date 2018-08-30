<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\DependencyInjection;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
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

        /** @var ArrayNodeDefinition $configurationsArrayDefinition */
        $configurationsArrayDefinition = $definition
            ->arrayNode('configurations')
            ->useAttributeAsKey('code')
            ->prototype('array');

        // Process list
        $processListDefinition = $configurationsArrayDefinition
            ->performNoDeepMerging()
            ->cannotBeOverwritten()
            ->children();

        $this->appendProcessConfigDefinition($processListDefinition);

        $processListDefinition->end();
        $configurationsArrayDefinition->end();
        $definition->end();

        return $treeBuilder;
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
        $definition
            ->scalarNode('service')->isRequired()->end()
            ->arrayNode('options')->prototype('variable')->end()->end()
            ->arrayNode('outputs')->prototype('scalar')->defaultValue([])->end()->end()
            ->arrayNode('errors')->prototype('scalar')->defaultValue([])->end()->end()
            ->scalarNode('error_strategy')->defaultValue(TaskConfiguration::STRATEGY_SKIP)->end()
            ->booleanNode('log_errors')->defaultTrue()->end();
    }
}
