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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds the service locator of the task registry from the services referenced by the process configurations, so
 * that the tasks are not fetched from the whole service container.
 *
 * @internal
 */
final class TaskLocatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('cleverage_process.registry.task')
            || !$container->hasDefinition('cleverage_process.registry.process_configuration')) {
            return;
        }

        $configurations = $container->getParameterBag()->resolveValue(
            $container->getDefinition('cleverage_process.registry.process_configuration')->getArgument(0)
        );
        $services = [];
        foreach (\is_array($configurations) ? $configurations : [] as $configuration) {
            foreach ($configuration['tasks'] ?? [] as $task) {
                $id = ltrim((string) ($task['service'] ?? ''), '@');
                // Unknown services keep failing at runtime, with the same message
                if ('' !== $id && $container->has($id)) {
                    $services[$id] = new Reference($id);
                }
            }
        }

        $container->getDefinition('cleverage_process.registry.task')
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
