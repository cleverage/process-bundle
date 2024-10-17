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

namespace CleverAge\ProcessBundle;

use CleverAge\ProcessBundle\DependencyInjection\Compiler\CheckSerializerCompilerPass;
use CleverAge\ProcessBundle\DependencyInjection\Compiler\RegistryCompilerPass;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CleverAgeProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new RegistryCompilerPass(TransformerRegistry::class, 'cleverage.transformer', 'addTransformer'),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            0
        );

        $container->addCompilerPass(new CheckSerializerCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
