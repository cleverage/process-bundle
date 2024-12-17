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
            new RegistryCompilerPass('cleverage_process.registry.transformer', 'cleverage.transformer', 'addTransformer')
        );

        $container->addCompilerPass(new CheckSerializerCompilerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
