<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle;

use CleverAge\ProcessBundle\DependencyInjection\Compiler\RegistryCompilerPass;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Soap\Registry as SoapRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author  Valentin Clavreul <vclavreul@clever-age.com>
 * @author  Vincent Chalnot <vchalnot@clever-age.com>
 */
class CleverAgeProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new RegistryCompilerPass(
                TransformerRegistry::class,
                'cleverage.transformer',
                'addTransformer'
            )
        );

        if (extension_loaded('soap')) {
            $container->addCompilerPass(
                new RegistryCompilerPass(
                    SoapRegistry::class,
                    'cleverage.soap.client',
                    'addClient'
                )
            );
        }
    }
}
