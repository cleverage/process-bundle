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

namespace CleverAge\ProcessBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Check the presence of the serializer (required for this bundle), and help the user to set it.
 *
 * @deprecated since 5.4, no longer used by the bundle, which compiles without the serializer: the services depending
 *             on it fail when they are used (see UnavailableServicesCompilerPass). Will be removed in v6.0.
 */
class CheckSerializerCompilerPass implements CompilerPassInterface
{
    final public const MSG = 'The Symfony serializer component do not seem enabled, consider toggling framework.serializer.enable (see https://symfony.com/doc/current/reference/configuration/framework.html#reference-serializer-enabled)';

    public function __construct()
    {
        trigger_deprecation('cleverage/process-bundle', '5.4', 'The "%s" class is deprecated, it will be removed in v6.0.', self::class);
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('serializer') && !$container->has(DenormalizerInterface::class)) {
            throw new AutowiringFailedException('serializer', self::MSG);
        }
    }
}
