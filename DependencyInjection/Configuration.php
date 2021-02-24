<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\DependencyInjection;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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
        [$treeBuilder, $rootNode] = $this->createTreeBuilder($this->root);
        $definition = $rootNode->children();
        // Default error strategy
        $definition->enumNode('default_error_strategy')
            ->values(
                [
                    TaskConfiguration::STRATEGY_SKIP,
                    TaskConfiguration::STRATEGY_STOP,
                ]
            )
            ->isRequired();

        $this->appendRootProcessConfigDefinition($definition);
        $this->appendRootTransformersConfigDefinition($definition);

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

        $definition->scalarNode('service')->isRequired();
        $definition->scalarNode('description')->defaultValue('');
        $definition->scalarNode('help')->defaultValue('');
        $definition->arrayNode('options')->prototype('variable')->end();
        $definition->scalarNode('error_strategy')->defaultNull();
        $definition->enumNode('log_level')->values($logLevels)->defaultValue(LogLevel::CRITICAL);

        $logErrorNode = $definition->booleanNode('log_errors')->defaultTrue();
        $this->deprecateNode(
            $logErrorNode,
            'cleverage/process-bundle',
            '2.0',
            'The child node "%node%" at path "%path%" is deprecated in favor of "log_level".'
        );

        foreach (['outputs', 'errors', 'error_outputs'] as $nodeName) {
            $definition->arrayNode($nodeName)
                ->beforeNormalization()
                ->ifString()->then(
                    function ($item) {
                        return [$item];
                    }
                )->end()
                ->prototype('scalar');
        }
    }

    /**
     * An helper method to deprecate a node.
     * Provides compatibility with Sf3, 4 and 5
     *
     * @TODO remove this once support for Symfony 3 and 4 is dropped
     *
     * @param NodeDefinition $node
     * @param string         $package
     * @param string         $version
     * @param string         $message
     */
    protected function deprecateNode(NodeDefinition $node, string $package, string $version, string $message)
    {
        $deprecationMethodReflection = new \ReflectionMethod(NodeDefinition::class, 'setDeprecated');
        if ($deprecationMethodReflection->getNumberOfParameters() === 1) {
            $node->setDeprecated("Since {$package} {$version}: {$message}");
        } else {
            $node->setDeprecated($package, $version, $message);
        }
    }

    /**
     * An helper method to create a TreeBuilder and get the root node.
     * Provides compatibility with Sf3, 4 and 5
     *
     * @TODO remove this once support for Symfony 3 and 4 is dropped
     *
     * @param string $root
     *
     * @return array A tuple containing [TreeBuilder, NodeDefinition]
     */
    protected function createTreeBuilder(string $root): array
    {
        $treeBuilderReflection = new \ReflectionClass(TreeBuilder::class);
        $treeBuilderConstructReflection = $treeBuilderReflection->getConstructor();

        if ($treeBuilderConstructReflection && $treeBuilderConstructReflection->getNumberOfParameters() > 0) {
            $treeBuilder = new TreeBuilder($this->root);
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root($this->root);
        }

        return [$treeBuilder, $rootNode];
    }
}
