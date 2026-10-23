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
 * Builds the service locator of the task registry from the services referenced by the process configurations, so
 * that the tasks are not fetched from the whole service container. The `task` codes of the configurations are first
 * resolved into the service with this code (`cleverage.task` tag, `#[AsTask]`).
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
        $processConfigurationRegistry = $container->getDefinition('cleverage_process.registry.process_configuration');
        $processConfigurationRegistry->replaceArgument(
            0,
            $this->resolveTaskCodes($container, $processConfigurationRegistry->getArgument(0))
        );

        $configurations = $container->getParameterBag()->resolveValue($processConfigurationRegistry->getArgument(0));
        $services = [];
        foreach (\is_array($configurations) ? $configurations : [] as $processCode => $configuration) {
            $taskCodesByService = [];
            foreach ($configuration['tasks'] ?? [] as $taskCode => $task) {
                $id = ltrim((string) ($task['service'] ?? ''), '@');
                // Unknown services keep failing at runtime, with the same message
                if ('' !== $id && $container->has($id)) {
                    $services[$id] = new Reference($id);
                    $taskCodesByService[$id][] = (string) $taskCode;
                }
            }
            $this->checkSharedServices($container, (string) $processCode, $taskCodesByService);
        }

        $container->getDefinition('cleverage_process.registry.task')
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $services));
    }

    /**
     * A shared service referenced by several tasks of a process would run all of them with the same instance: the
     * options of the last initialization would apply to every task, and their states would be mixed.
     *
     * @param array<string, list<string>> $taskCodesByService
     */
    private function checkSharedServices(ContainerBuilder $container, string $processCode, array $taskCodesByService): void
    {
        foreach ($taskCodesByService as $id => $taskCodes) {
            if (\count($taskCodes) > 1 && $container->findDefinition($id)->isShared()) {
                throw new InvalidArgumentException(\sprintf('The service "%s" is used by the tasks "%s" of the process "%s", but it is shared: all these tasks would run with the same instance (and the options of one of them). Declare the service with "shared: false".', $id, implode('", "', $taskCodes), $processCode));
            }
        }
    }

    /**
     * Sets the "service" of the tasks configured with a "task" code. The configurations are not resolved (parameters),
     * only the "service" key is set.
     */
    private function resolveTaskCodes(ContainerBuilder $container, mixed $configurations): mixed
    {
        if (!\is_array($configurations)) {
            return $configurations;
        }
        $codes = null;
        foreach ($configurations as $processCode => $configuration) {
            foreach ($configuration['tasks'] ?? [] as $taskCode => $task) {
                if (null === ($task['task'] ?? null)) {
                    continue;
                }
                $codes ??= $this->getTaskCodes($container);
                $code = $container->getParameterBag()->resolveValue($task['task']);
                if (!\is_string($code) || !isset($codes[$code])) {
                    $known = array_keys($codes);
                    sort($known);
                    throw new InvalidArgumentException(\sprintf('Unknown task code "%s" for the task "%s" of the process "%s". Known codes: "%s".', \is_string($code) ? $code : get_debug_type($code), $taskCode, $processCode, implode('", "', $known)));
                }
                $configurations[$processCode]['tasks'][$taskCode]['service'] = '@'.$codes[$code];
            }
        }

        return $configurations;
    }

    /**
     * @return array<string, string> service id by task code
     */
    private function getTaskCodes(ContainerBuilder $container): array
    {
        $codes = [];
        foreach ($container->findTaggedServiceIds('cleverage.task') as $id => $tags) {
            foreach ($tags as $attributes) {
                $code = $attributes['code'] ?? null;
                if (!\is_string($code) || '' === $code) {
                    throw new InvalidArgumentException(\sprintf('The "code" attribute of the "cleverage.task" tag of the "%s" service must be a non-empty string.', $id));
                }
                if (isset($codes[$code]) && $codes[$code] !== $id) {
                    throw new InvalidArgumentException(\sprintf('The task code "%s" is used by the "%s" and "%s" services.', $code, $codes[$code], $id));
                }
                $codes[$code] = $id;
            }
        }

        return $codes;
    }
}
