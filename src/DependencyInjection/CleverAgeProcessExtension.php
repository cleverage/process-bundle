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

namespace CleverAge\ProcessBundle\DependencyInjection;

use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Transformer\GenericTransformer;
use Exception;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;
use function dirname;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see    http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class CleverAgeProcessExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Get the path of the service folder wherever the bundle is installed
        $reflection = new ReflectionClass($this);
        $serviceFolderPath = dirname($reflection->getFileName(), 2) . '/Resources/config/services';
        $this->findServices($container, $serviceFolderPath);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $processConfigurationRegistry = $container->getDefinition(ProcessConfigurationRegistry::class);
        $processConfigurationRegistry->replaceArgument(0, $config['configurations']);
        $processConfigurationRegistry->replaceArgument(1, $config['default_error_strategy']);

        // Automatic transformer creation from config
        foreach ($config['generic_transformers'] as $transformerCode => $transformerConfig) {
            $transformerDefinition = new Definition(GenericTransformer::class);
            $transformerDefinition->setAutowired(true);
            $transformerDefinition->setPublic(false);
            $transformerDefinition->addMethodCall('initialize', [$transformerCode, $transformerConfig]);
            $transformerDefinition->addTag('cleverage.transformer');

            $container->setDefinition(GenericTransformer::class . '\\' . $transformerCode, $transformerDefinition);
        }
    }

    /**
     * Recursively import config files into container
     *
     * @throws Exception
     */
    protected function findServices(ContainerBuilder $container, string $path, string $extension = 'yaml'): void
    {
        $finder = new Finder();
        $finder->in($path)
            ->name('*.' . $extension)->files();
        $loader = new YamlFileLoader($container, new FileLocator($path));
        foreach ($finder as $file) {
            $loader->load($file->getFilename());
        }
    }
}
