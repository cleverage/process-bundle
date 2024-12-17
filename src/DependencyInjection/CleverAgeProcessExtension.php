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

use CleverAge\ProcessBundle\Transformer\GenericTransformer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see https://symfony.com/doc/current/bundles/extension.html
 */
class CleverAgeProcessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Get the path of the service folder wherever the bundle is installed
        $this->findServices($container, __DIR__.'/../../config/services');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $processConfigurationRegistry = $container->getDefinition('cleverage_process.registry.process_configuration');
        $processConfigurationRegistry->replaceArgument(0, $config['configurations']);
        $processConfigurationRegistry->replaceArgument(1, $config['default_error_strategy']);

        // Automatic transformer creation from config
        foreach ($config['generic_transformers'] as $transformerCode => $transformerConfig) {
            $transformerDefinition = new Definition(GenericTransformer::class);
            $transformerDefinition->setAutowired(true);
            $transformerDefinition->setPublic(false);
            $transformerDefinition->setArguments([
                new Reference('cleverage_process.context.contextual_option_resolver'),
                new Reference('cleverage_process.registry.transformer'),
            ]);
            $transformerDefinition->addMethodCall('initialize', [$transformerCode, $transformerConfig]);
            $transformerDefinition->addTag('cleverage.transformer');

            $container->setDefinition(GenericTransformer::class.'\\'.$transformerCode, $transformerDefinition);
        }
    }

    /**
     * Recursively import config files into container.
     */
    protected function findServices(ContainerBuilder $container, string $path, string $extension = 'yaml'): void
    {
        $finder = new Finder();
        $finder->in($path)
            ->name('*.'.$extension)->files();
        $loader = new YamlFileLoader($container, new FileLocator($path));
        foreach ($finder as $file) {
            $loader->load($file->getFilename());
        }
    }
}
