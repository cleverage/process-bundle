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

use CleverAge\ProcessBundle\Attribute\AsTask;
use CleverAge\ProcessBundle\Attribute\AsTransformer;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\ProcessStopCheckerInterface;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Transformer\GenericTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see https://symfony.com/doc/current/bundles/extension.html
 *
 * @internal
 */
class CleverAgeProcessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Get the path of the service folder wherever the bundle is installed
        $this->findServices($container, __DIR__.'/../../config/services');

        $container->registerForAutoconfiguration(ProcessStopCheckerInterface::class)
            ->addTag('cleverage_process.stop_checker');
        // Tasks keep state between executions. Public for the deprecated fallback of the TaskRegistry on the service
        // container: their direct access is deprecated (PublicTaskDeprecationCompilerPass), private in v6.0
        $container->registerForAutoconfiguration(TaskInterface::class)
            ->setPublic(true)
            ->setShared(false)
            ->addTag('monolog.logger', ['channel' => 'cleverage_process_task']);
        $container->registerForAutoconfiguration(TransformerInterface::class)
            ->addTag('cleverage.transformer')
            ->addTag('monolog.logger', ['channel' => 'cleverage_process_transformer']);
        // Tasks referenced by code (task: <code>)
        $container->registerAttributeForAutoconfiguration(
            AsTask::class,
            static function (ChildDefinition $definition, AsTask $attribute): void {
                $definition->addTag('cleverage.task', ['code' => $attribute->code]);
            }
        );
        // Lazy loading of the transformers by code
        $container->registerAttributeForAutoconfiguration(
            AsTransformer::class,
            static function (ChildDefinition $definition, AsTransformer $attribute): void {
                $definition->addTag('cleverage.transformer', ['code' => $attribute->code]);
            }
        );

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->checkErrorStrategies($container, $config['configurations']);

        $processConfigurationRegistry = $container->getDefinition('cleverage_process.registry.process_configuration');
        $processConfigurationRegistry->replaceArgument(0, $config['configurations']);
        $processConfigurationRegistry->replaceArgument(1, $config['default_error_strategy']);
        $processConfigurationRegistry->replaceArgument(2, $config['logs']);

        // Automatic transformer creation from config
        foreach ($config['generic_transformers'] as $transformerCode => $transformerConfig) {
            $transformerDefinition = new Definition(GenericTransformer::class);
            $transformerDefinition->setPublic(false);
            $transformerDefinition->setArguments([
                new Reference('cleverage_process.context.contextual_option_resolver'),
                new Reference('cleverage_process.registry.transformer'),
            ]);
            $transformerDefinition->addMethodCall('initialize', [$transformerCode, $transformerConfig]);
            $transformerDefinition->addTag('cleverage.transformer', ['code' => $transformerCode]);

            $container->setDefinition(GenericTransformer::class.'\\'.$transformerCode, $transformerDefinition);
        }
    }

    /**
     * An unknown error strategy only fails when the task fails ("Unknown error strategy").
     *
     * @param array<string, array{tasks: array<string, array{error_strategy: mixed}>}> $configurations
     */
    protected function checkErrorStrategies(ContainerBuilder $container, array $configurations): void
    {
        $strategies = [TaskConfiguration::STRATEGY_SKIP, TaskConfiguration::STRATEGY_STOP];
        foreach ($configurations as $processCode => $processConfiguration) {
            foreach ($processConfiguration['tasks'] as $taskCode => $taskConfiguration) {
                // Not set (default_error_strategy), or not a string (already a TypeError in TaskConfiguration)
                $errorStrategy = $taskConfiguration['error_strategy'];
                if (!\is_string($errorStrategy) || \in_array($errorStrategy, $strategies, true)) {
                    continue;
                }
                // An environment variable is only known at runtime
                if (str_contains((string) $container->resolveEnvPlaceholders($errorStrategy), '%env(')) {
                    continue;
                }
                trigger_deprecation(
                    'cleverage/process-bundle',
                    '5.4',
                    'The error strategy "%s" of the task "%s" of the process "%s" is unknown, use "skip" or "stop" (or remove it to use the "default_error_strategy"). Unknown error strategies are deprecated, they will be rejected in v6.0.',
                    $errorStrategy,
                    $taskCode,
                    $processCode
                );
            }
        }
    }

    /**
     * Import the service definition files of a directory into the container.
     */
    protected function findServices(ContainerBuilder $container, string $path, string $extension = 'yaml'): void
    {
        $loader = new YamlFileLoader($container, new FileLocator($path));
        $loader->import('*.'.$extension, 'glob');
    }
}
