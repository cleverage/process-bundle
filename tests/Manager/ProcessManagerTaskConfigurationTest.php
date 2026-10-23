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
use CleverAge\ProcessBundle\Exception\ProcessFailedException;
use CleverAge\ProcessBundle\Logger\AbstractLogger;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The task configuration returned by ProcessManager::getTaskConfiguration() (used by the log processors) after a child
 * task or a sub-process.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessFailedException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractLogger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfigurationRegistry::class)]
class ProcessManagerTaskConfigurationTest extends TestCase
{
    private Container $container;

    private ProcessConfigurationRegistry $registry;

    private ProcessManager $processManager;

    /**
     * The task codes seen by the tasks while the process was running.
     *
     * @var list<?string>
     */
    private array $observed = [];

    protected function setUp(): void
    {
        $container = $this->container = new Container();
        // Emits 2 items, and records the current task configuration each time
        $container->set('test.iterate', new class($this) implements IterableTaskInterface {
            private int $index = 0;

            public function __construct(
                private readonly ProcessManagerTaskConfigurationTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->observe();
                $state->setOutput($this->index);
            }

            public function next(ProcessState $state): bool
            {
                return ++$this->index < 2;
            }
        });
        $container->set('test.noop', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
            }
        });
        $container->set('test.failing', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
                throw new \LogicException('Something went wrong');
            }
        });
        // Runs the given sub-process (ignoring its failure, as the process_executor task with the skip error
        // strategy), then records the current task configuration
        $container->set('test.sub_process', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerTaskConfigurationTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->executeSubProcess((string) $state->getTaskConfiguration()->getOption('process'));
                $this->test->observe();
            }
        });

        $this->registry = new ProcessConfigurationRegistry(
            [
                'test.iterate' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.iterate', [], ['child']),
                    'child' => $this->createTaskConfiguration('@test.noop'),
                ]),
                'test.parent' => $this->createProcessConfiguration([
                    'sub_process' => $this->createTaskConfiguration('@test.sub_process', ['process' => 'test.child']),
                ]),
                'test.child' => $this->createProcessConfiguration([
                    'child_task' => $this->createTaskConfiguration('@test.noop'),
                ]),
                'test.failing_parent' => $this->createProcessConfiguration([
                    'sub_process' => $this->createTaskConfiguration('@test.sub_process', ['process' => 'test.failing']),
                ]),
                'test.failing' => $this->createProcessConfiguration([
                    'fail' => $this->createTaskConfiguration('@test.failing'),
                ]),
            ],
            'stop'
        );

        $this->processManager = $this->createProcessManager(new EventDispatcher());
    }

    public function observe(): void
    {
        $this->observed[] = $this->processManager->getTaskConfiguration()?->getCode();
    }

    public function executeSubProcess(string $processCode): void
    {
        try {
            $this->processManager->execute($processCode);
        } catch (ProcessFailedException) {
        }
    }

    public function testTheIterableTaskIsRestoredAfterItsChildTask(): void
    {
        $this->processManager->execute('test.iterate');

        self::assertSame(['iterate', 'iterate'], $this->observed);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    public function testTheTaskIsRestoredAfterASubProcess(): void
    {
        $this->processManager->execute('test.parent');

        self::assertSame(['sub_process'], $this->observed);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    public function testTheTaskIsRestoredAfterAFailedSubProcess(): void
    {
        $this->processManager->execute('test.failing_parent');

        self::assertSame(['sub_process'], $this->observed);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    public function testTheFailingTaskIsKeptForTheLogsOfTheFailure(): void
    {
        $failingTask = null;
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ProcessEvent::EVENT_PROCESS_FAILED, function () use (&$failingTask): void {
            $failingTask = $this->processManager->getTaskConfiguration()?->getCode();
        });
        $this->processManager = $this->createProcessManager($dispatcher);

        try {
            $this->processManager->execute('test.failing');
            self::fail('The process should have failed');
        } catch (ProcessFailedException) {
        }

        self::assertSame('fail', $failingTask);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    private function createProcessManager(EventDispatcher $eventDispatcher): ProcessManager
    {
        return new ProcessManager(
            $this->container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $this->registry,
            new ContextualOptionResolver(),
            $eventDispatcher,
            taskRegistry: new TaskRegistry($this->container),
        );
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     *
     * @return array<string, mixed>
     */
    private function createProcessConfiguration(array $tasks): array
    {
        return [
            'options' => [],
            'entry_point' => null,
            'end_point' => null,
            'description' => '',
            'help' => '',
            'public' => true,
            'tasks' => $tasks,
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string>         $outputs
     *
     * @return array<string, mixed>
     */
    private function createTaskConfiguration(string $service, array $options = [], array $outputs = []): array
    {
        return [
            'service' => $service,
            'options' => $options,
            'description' => '',
            'help' => '',
            'outputs' => $outputs,
            'errors' => [],
            'error_outputs' => [],
            'error_strategy' => null,
            'log_level' => null,
        ];
    }
}
