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

namespace CleverAge\ProcessBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Check the presence of the serializer (required for this bundle), and help the user to set it
 */
class CheckSerializerCompilerPass implements CompilerPassInterface
{
    final public const MSG = 'The Symfony serializer component do not seem enabled, consider toggling framework.serializer.enable (see https://symfony.com/doc/current/reference/configuration/framework.html#reference-serializer-enabled)';

    public function process(ContainerBuilder $container)
    {
        if (! $container->has('serializer') && ! $container->has(DenormalizerInterface::class)) {
            throw new AutowiringFailedException('serializer', self::MSG);
        }
    }
}
