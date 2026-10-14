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

namespace CleverAge\ProcessBundle\Tests\Registry;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Task\DummyTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(TaskRegistry::class)]
#[UsesClass(TaskConfiguration::class)]
class TaskRegistryTest extends TestCase
{
    /** @var list<string> */
    private array $deprecations = [];

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideServiceReferences(): iterable
    {
        yield 'with @' => ['@app.task'];
        yield 'without @' => ['app.task'];
    }

    #[DataProvider('provideServiceReferences')]
    public function testTaskIsFetchedFromTheLocator(string $serviceReference): void
    {
        $registry = new TaskRegistry($this->createLocator(), $this->createContainer());

        $task = $this->getTask($registry, $serviceReference);

        self::assertInstanceOf(DummyTask::class, $task);
        self::assertSame([], $this->deprecations);
    }

    public function testLocatorCreatesANewInstanceForEachTask(): void
    {
        $registry = new TaskRegistry($this->createLocator());

        self::assertNotSame($this->getTask($registry, '@app.task'), $this->getTask($registry, '@app.task'));
    }

    #[DataProvider('provideServiceReferences')]
    public function testFallbackOnTheContainerIsDeprecated(string $serviceReference): void
    {
        $container = $this->createContainer();
        $registry = new TaskRegistry(new ServiceLocator([]), $container);

        $task = $this->getTask($registry, $serviceReference);

        self::assertSame($container->get('app.task'), $task);
        self::assertSame(
            ['Since cleverage/process-bundle 5.4: Fetching the service "app.task" of the task "task" from the service container is deprecated: it is not referenced by the process configurations known when the container is compiled. It will not be found anymore in v6.0.'],
            $this->deprecations
        );
    }

    public function testUnknownServiceWithoutAtThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Unable to resolve service reference for Task 'task'");

        $this->getTask(new TaskRegistry(new ServiceLocator([]), new Container()), 'app.unknown');
    }

    public function testUnknownServiceWithAtThrowsAsTheContainer(): void
    {
        // Same exception as before the task registry
        $this->expectException(ServiceNotFoundException::class);

        $this->getTask(new TaskRegistry(new ServiceLocator([]), new Container()), '@app.unknown');
    }

    public function testUnknownServiceWithoutFallbackThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Unable to resolve service reference for Task 'task'");

        $this->getTask(new TaskRegistry(new ServiceLocator([])), '@app.unknown');
    }

    public function testServiceThatIsNotATaskThrows(): void
    {
        $registry = new TaskRegistry(new ServiceLocator(['app.task' => static fn (): \stdClass => new \stdClass()]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Service defined in Task 'task' is not a TaskInterface");

        $this->getTask($registry, '@app.task');
    }

    /**
     * @return ServiceLocator<DummyTask>
     */
    private function createLocator(): ServiceLocator
    {
        return new ServiceLocator(['app.task' => static fn (): DummyTask => new DummyTask()]);
    }

    private function createContainer(): Container
    {
        $container = new Container();
        $container->set('app.task', new DummyTask());

        return $container;
    }

    private function getTask(TaskRegistry $registry, string $serviceReference): object
    {
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            return $registry->getTask(new TaskConfiguration('task', $serviceReference, []));
        } finally {
            restore_error_handler();
        }
    }
}
