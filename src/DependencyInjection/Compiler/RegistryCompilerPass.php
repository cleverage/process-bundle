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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Generic compiler pass to add tagged services to a registry.
 */
class RegistryCompilerPass implements CompilerPassInterface
{
    public function __construct(
        protected ?string $registry = null,
        protected ?string $tag = null,
        protected ?string $method = null
    ) {
    }

    /**
     * Inject tagged services into defined registry.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has($this->registry)) {
            return;
        }

        $definition = $container->findDefinition($this->registry);
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall($this->method, [new Reference($id)]);
        }
    }
}
