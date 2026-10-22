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
use CleverAge\ProcessBundle\Tests\IgnoreDeprecationsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(TaskLocatorCompilerPass::class)]
#[UsesClass(CleverAgeProcessExtension::class)]
#[UsesClass(Configuration::class)]
class TaskLocatorCompilerPassTest extends TestCase
{
    use IgnoreDeprecationsTrait;

    public function testLocatorContainsTheServicesReferencedByTheProcesses(): void
    {
        $container = new ContainerBuilder();
        // Deprecated "service" option
        self::ignoreDeprecations(static fn () => (new CleverAgeProcessExtension())->load([[
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
        ]], $container));
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

    public function testTaskCodesAreResolvedIntoServices(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([[
            'configurations' => [
                'test.codes' => ['tasks' => [
                    'bundle' => ['task' => 'dummy', 'options' => ['format' => '%%s']],
                    'application' => ['task' => 'app_task'],
                ]],
            ],
        ]], $container);
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->addTag('cleverage.task', ['code' => 'app_task']));

        (new TaskLocatorCompilerPass())->process($container);

        $configurations = $container->getDefinition('cleverage_process.registry.process_configuration')->getArgument(0);
        self::assertIsArray($configurations);
        $tasks = $configurations['test.codes']['tasks'];
        self::assertSame('@'.DummyTask::class, $tasks['bundle']['service']);
        self::assertSame('@app.task', $tasks['application']['service']);
        // Only the service is set: the parameters of the configuration are not resolved (escaped %)
        self::assertSame(['format' => '%%s'], $tasks['bundle']['options']);

        $locatorReference = $container->getDefinition('cleverage_process.registry.task')->getArgument(0);
        self::assertInstanceOf(Reference::class, $locatorReference);
        $services = $container->getDefinition((string) $locatorReference)->getArgument(0);
        self::assertIsArray($services);
        self::assertSame([DummyTask::class, 'app.task'], array_keys($services));
    }

    public function testUnknownTaskCodeThrows(): void
    {
        $container = $this->createContainerWithTask(['task' => 'unknown']);
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->addTag('cleverage.task', ['code' => 'app_task']));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown task code "unknown" for the task "my_task" of the process "test.process". Known codes: "app_task".');

        (new TaskLocatorCompilerPass())->process($container);
    }

    public function testDuplicateTaskCodeThrows(): void
    {
        $container = $this->createContainerWithTask(['task' => 'same']);
        $container->setDefinition('app.first', (new Definition(DummyTask::class))->addTag('cleverage.task', ['code' => 'same']));
        $container->setDefinition('app.second', (new Definition(DummyTask::class))->addTag('cleverage.task', ['code' => 'same']));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The task code "same" is used by the "app.first" and "app.second" services.');

        (new TaskLocatorCompilerPass())->process($container);
    }

    public function testInvalidTaskCodeAttributeThrows(): void
    {
        $container = $this->createContainerWithTask(['task' => 'app_task']);
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->addTag('cleverage.task'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "code" attribute of the "cleverage.task" tag of the "app.task" service must be a non-empty string.');

        (new TaskLocatorCompilerPass())->process($container);
    }

    public function testSharedServiceUsedBySeveralTasksOfAProcessThrows(): void
    {
        $container = $this->createContainerWithTasks(['test.process' => ['first' => 'app_task', 'second' => 'app_task']]);
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->addTag('cleverage.task', ['code' => 'app_task']));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "app.task" is used by the tasks "first", "second" of the process "test.process", but it is shared: all these tasks would run with the same instance (and the options of one of them). Declare the service with "shared: false".');

        (new TaskLocatorCompilerPass())->process($container);
    }

    public function testNonSharedServiceCanBeUsedBySeveralTasksOfAProcess(): void
    {
        $container = $this->createContainerWithTasks(['test.process' => ['first' => 'app_task', 'second' => 'app_task']]);
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->setShared(false)->addTag('cleverage.task', ['code' => 'app_task']));

        (new TaskLocatorCompilerPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testSharedServiceCanBeUsedOnceByEachProcess(): void
    {
        $container = $this->createContainerWithTasks(['test.first' => ['task' => 'app_task'], 'test.second' => ['task' => 'app_task']]);
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->addTag('cleverage.task', ['code' => 'app_task']));

        (new TaskLocatorCompilerPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testNothingIsDoneWithoutTheRegistries(): void
    {
        $container = new ContainerBuilder();

        (new TaskLocatorCompilerPass())->process($container);

        self::assertFalse($container->hasDefinition('cleverage_process.registry.task'));
    }

    /**
     * @param array<string, mixed> $task
     */
    private function createContainerWithTask(array $task): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([['configurations' => ['test.process' => ['tasks' => ['my_task' => $task]]]]], $container);

        return $this->clearTaskTags($container);
    }

    /**
     * @param array<string, array<string, string>> $taskCodesByProcess task code by task, for each process
     */
    private function createContainerWithTasks(array $taskCodesByProcess): ContainerBuilder
    {
        $configurations = [];
        foreach ($taskCodesByProcess as $processCode => $taskCodes) {
            $configurations[$processCode]['tasks'] = array_map(static fn (string $code): array => ['task' => $code], $taskCodes);
        }
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([['configurations' => $configurations]], $container);

        return $this->clearTaskTags($container);
    }

    private function clearTaskTags(ContainerBuilder $container): ContainerBuilder
    {
        // Only the tasks of the test
        foreach (array_keys($container->findTaggedServiceIds('cleverage.task')) as $id) {
            $container->getDefinition($id)->clearTag('cleverage.task');
        }

        return $container;
    }
}
