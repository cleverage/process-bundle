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

namespace CleverAge\ProcessBundle\Tests\DependencyInjection\Compiler;

use CleverAge\ProcessBundle\DependencyInjection\CleverAgeProcessExtension;
use CleverAge\ProcessBundle\DependencyInjection\Compiler\TaskLocatorCompilerPass;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use CleverAge\ProcessBundle\Task\DummyTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(TaskLocatorCompilerPass::class)]
#[UsesClass(CleverAgeProcessExtension::class)]
#[UsesClass(Configuration::class)]
class TaskLocatorCompilerPassTest extends TestCase
{
    public function testLocatorContainsTheServicesReferencedByTheProcesses(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([[
            'configurations' => [
                'test.first' => ['tasks' => [
                    'dummy' => ['service' => '@'.DummyTask::class],
                    'private' => ['service' => 'app.private_task'],
                ]],
                'test.second' => ['tasks' => [
                    'dummy_again' => ['service' => DummyTask::class],
                    'unknown' => ['service' => '@app.unknown'],
                ]],
            ],
        ]], $container);
        // Private services can be fetched from the locator
        $container->setDefinition('app.private_task', (new Definition(DummyTask::class))->setPublic(false));

        (new TaskLocatorCompilerPass())->process($container);

        $locatorReference = $container->getDefinition('cleverage_process.registry.task')->getArgument(0);
        self::assertInstanceOf(Reference::class, $locatorReference);
        $locator = $container->getDefinition((string) $locatorReference);
        self::assertSame(ServiceLocator::class, $locator->getClass());
        $services = $locator->getArgument(0);
        self::assertIsArray($services);
        // Unknown services are left out: they keep failing at runtime
        self::assertSame([DummyTask::class, 'app.private_task'], array_keys($services));
        foreach ($services as $id => $service) {
            self::assertInstanceOf(ServiceClosureArgument::class, $service);
            self::assertSame($id, (string) $service->getValues()[0]);
        }
    }

    public function testNothingIsDoneWithoutTheRegistries(): void
    {
        $container = new ContainerBuilder();

        (new TaskLocatorCompilerPass())->process($container);

        self::assertFalse($container->hasDefinition('cleverage_process.registry.task'));
    }
}
