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

use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The autoconfiguration of TaskInterface makes the tasks of the application public, for the deprecated fallback of
 * the TaskRegistry on the service container: fetching them directly from the container is deprecated (container.private
 * tag, as for the tasks of the bundle), they will be private in v6.0. The tasks made public by the application itself
 * (service, "_defaults" or "_instanceof" configuration, #[Autoconfigure] attribute) are not concerned.
 *
 * Runs before ResolveInstanceofConditionalsPass, which merges the autoconfiguration into the definitions.
 *
 * @internal
 */
final class PublicTaskDeprecationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition->isAutoconfigured()
                || $definition->isAbstract()
                || $definition->isSynthetic()
                || $definition->hasTag('container.private')
                || isset($definition->getChanges()['public'])) {
                continue;
            }
            // Same resolution as ResolveClassPass for the services whose id is the class
            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
            if (!\is_string($class)
                || !($reflectionClass = $container->getReflectionClass($class, false)) instanceof \ReflectionClass
                || !$reflectionClass->implementsInterface(TaskInterface::class)) {
                continue;
            }
            foreach ($definition->getInstanceofConditionals() as $type => $instanceof) {
                if (isset($instanceof->getChanges()['public']) && is_a($class, $type, true)) {
                    continue 2;
                }
            }
            foreach ($reflectionClass->getAttributes(Autoconfigure::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                if (null !== $attribute->newInstance()->public) {
                    continue 2;
                }
            }

            $definition->addTag('container.private', ['package' => 'cleverage/process-bundle', 'version' => '5.4']);
        }
    }
}
