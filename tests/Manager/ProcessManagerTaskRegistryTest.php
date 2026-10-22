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

namespace CleverAge\ProcessBundle\Tests\Manager;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Event\ProcessEvent;
use CleverAge\ProcessBundle\Logger\AbstractLogger;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Task\ConstantOutputTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The tasks are fetched with the task registry; without it (deprecated), from the service container.
 */
#[CoversClass(ProcessManager::class)]
#[UsesClass(TaskRegistry::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ContextualOptionResolver::class)]
#[UsesClass(ProcessEvent::class)]
#[UsesClass(AbstractLogger::class)]
#[UsesClass(ProcessHistory::class)]
#[UsesClass(ProcessState::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(ConstantOutputTask::class)]
#[UsesClass(AbstractConfigurableTask::class)]
class ProcessManagerTaskRegistryTest extends TestCase
{
    /** @var list<string> */
    private array $deprecations = [];

    public function testTasksAreFetchedWithTheTaskRegistry(): void
    {
        $processManager = $this->collectDeprecations(fn (): ProcessManager => $this->createProcessManager(
            new TaskRegistry(new ServiceLocator(['app.constant' => static fn (): ConstantOutputTask => new ConstantOutputTask()]))
        ));

        self::assertSame('ok', $this->collectDeprecations(static fn (): mixed => $processManager->execute('test.process')));
        self::assertSame([], $this->deprecations);
    }

    public function testWithoutTaskRegistryTheTasksAreFetchedFromTheContainerWithADeprecation(): void
    {
        $processManager = $this->collectDeprecations(fn (): ProcessManager => $this->createProcessManager(null));

        self::assertSame('ok', $this->collectDeprecations(static fn (): mixed => $processManager->execute('test.process')));
        self::assertSame(
            ['Since cleverage/process-bundle 5.4: Not passing a "'.TaskRegistry::class.'" to "'.ProcessManager::class.'::__construct()" is deprecated: the tasks are fetched from the service container. The argument will be required in v6.0.'],
            $this->deprecations
        );
    }

    private function createProcessManager(?TaskRegistry $taskRegistry): ProcessManager
    {
        $container = new Container();
        $container->set('app.constant', new ConstantOutputTask());
        $registry = new ProcessConfigurationRegistry(
            [
                'test.process' => [
                    'options' => [],
                    'entry_point' => null,
                    'end_point' => 'constant',
                    'description' => '',
                    'help' => '',
                    'public' => true,
                    'tasks' => [
                        'constant' => [
                            'service' => '@app.constant',
                            'options' => ['output' => 'ok'],
                            'description' => '',
                            'help' => '',
                            'outputs' => [],
                            'errors' => [],
                            'error_outputs' => [],
                            'error_strategy' => null,
                            'log_level' => null,
                        ],
                    ],
                ],
            ],
            'stop'
        );

        return new ProcessManager(
            $container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $registry,
            new ContextualOptionResolver(),
            new EventDispatcher(),
            taskRegistry: $taskRegistry,
        );
    }

    /**
     * @template T
     *
     * @param callable(): T $callable
     *
     * @return T
     */
    private function collectDeprecations(callable $callable): mixed
    {
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            return $callable();
        } finally {
            restore_error_handler();
        }
    }
}
