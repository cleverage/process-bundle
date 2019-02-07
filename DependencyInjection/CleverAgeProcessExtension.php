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

use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Sidus\BaseBundle\DependencyInjection\Loader\ServiceLoader;
use Sidus\BaseBundle\DependencyInjection\SidusBaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see    http://symfony.com/doc/current/cookbook/bundles/extension.html
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CleverAgeProcessExtension extends SidusBaseExtension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        parent::load($configs, $container);

        $loader = new ServiceLoader($container);
        $bundles = $container->getParameter('kernel.bundles');

        if (class_exists('\Doctrine\ORM\Version')) {
            $serviceFolderPath = __DIR__.'/../Resources/config/services-doctrine';
            $loader->loadFiles($serviceFolderPath);
        }

        if (array_key_exists('OneupFlysystemBundle', $bundles)) {
            $serviceFolderPath = __DIR__.'/Resources/config/services-flysystem';
            $loader->loadFiles($serviceFolderPath);
        }

        if (extension_loaded('soap')) {
            $serviceFolderPath = __DIR__.'/../Resources/config/services-soap';
            $loader->loadFiles($serviceFolderPath);
        }

        if (class_exists('\Httpful\Request')) {
            $serviceFolderPath = __DIR__.'/../Resources/config/services-rest';
            $loader->loadFiles($serviceFolderPath);
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $processConfigurationRegistry = $container->getDefinition(ProcessConfigurationRegistry::class);
        $processConfigurationRegistry->replaceArgument(0, $config['configurations']);
        $processConfigurationRegistry->replaceArgument(1, $config['default_error_strategy']);
    }
}
