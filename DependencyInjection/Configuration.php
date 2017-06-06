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

namespace CleverAge\ProcessBundle\DependencyInjection;

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
            ->arrayNode('options')->prototype('variable')->end()->end()
        ;

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
            ->arrayNode('inputs')->prototype('scalar')->defaultValue([])->end()->end()
            ->arrayNode('options')->prototype('variable')->end()->end();
    }
}
