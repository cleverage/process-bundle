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
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the `cleverage.transformer` tagged services in the transformer registry: in a service locator indexed by
 * the `code` attribute of the tag (lazy), or, without `code` (deprecated), with an addTransformer() call (eager).
 *
 * @internal
 */
final class TransformerLocatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('cleverage_process.registry.transformer')) {
            return;
        }
        $registry = $container->getDefinition('cleverage_process.registry.transformer');

        $services = [];
        $ids = [];
        foreach ($container->findTaggedServiceIds('cleverage.transformer') as $id => $tags) {
            $codes = [];
            foreach ($tags as $attributes) {
                if (!isset($attributes['code'])) {
                    continue;
                }
                if (!\is_string($attributes['code']) || '' === $attributes['code']) {
                    throw new InvalidArgumentException(\sprintf('The "code" attribute of the "cleverage.transformer" tag of the "%s" service must be a non-empty string.', $id));
                }
                $codes[] = $attributes['code'];
            }
            if ([] === $codes) {
                // Deprecated: instantiated with the registry, addTransformer() triggers the deprecation
                $registry->addMethodCall('addTransformer', [new Reference($id)]);
                continue;
            }
            foreach (array_unique($codes) as $code) {
                if (isset($ids[$code])) {
                    throw new InvalidArgumentException(\sprintf('The transformer code "%s" is used by the "%s" and "%s" services.', $code, $ids[$code], $id));
                }
                $ids[$code] = $id;
                $services[$code] = new Reference($id);
            }
        }

        $registry->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
