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
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use CleverAge\ProcessBundle\Task\AggregateIterableTask;
use CleverAge\ProcessBundle\Task\ConstantIterableOutputTask;
use CleverAge\ProcessBundle\Task\SimpleBatchTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * A sub-process sharing the process manager of its parent (as the process_executor task) must not change the state of
 * the tasks of the parent, even when its tasks have the same codes.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractLogger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfigurationRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ConstantIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AggregateIterableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SimpleBatchTask::class)]
class ProcessManagerSubProcessTest extends TestCase
{
    private ProcessManager $processManager;

    /**
     * @var list<mixed>
     */
    private array $recorded = [];

    protected function setUp(): void
    {
        $container = new Container();
        $container->set('test.parent_iterate', new ConstantIterableOutputTask());
        $container->set('test.child_iterate', new ConstantIterableOutputTask());
        $container->set('test.parent_aggregate', new AggregateIterableTask());
        $container->set('test.child_aggregate', new AggregateIterableTask());
        $container->set('test.batch', new SimpleBatchTask());
        // Runs the given sub-process and forwards its input, as the process_executor task
        $container->set('test.sub_process', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerSubProcessTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->executeSubProcess((string) $state->getTaskConfiguration()->getOption('process'));
                $state->setOutput($state->getInput());
            }
        });
        $container->set('test.record', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerSubProcessTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->record($state->getInput());
            }
        });

        $registry = new ProcessConfigurationRegistry(
            [
                'test.blocking_parent' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.parent_iterate', ['output' => [1, 2, 3]], ['sub_process']),
                    'sub_process' => $this->createTaskConfiguration('@test.sub_process', ['process' => 'test.blocking_child'], ['aggregate']),
                    'aggregate' => $this->createTaskConfiguration('@test.parent_aggregate'),
                ], 'aggregate'),
                'test.blocking_child' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.child_iterate', ['output' => ['a']], ['aggregate']),
                    'aggregate' => $this->createTaskConfiguration('@test.child_aggregate'),
                ]),
                'test.iterable_parent' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.parent_iterate', ['output' => [1, 2, 3, 4, 5]], ['sub_process']),
                    'sub_process' => $this->createTaskConfiguration('@test.sub_process', ['process' => 'test.iterable_child'], ['batch']),
                    'batch' => $this->createTaskConfiguration('@test.batch', ['batch_count' => 2], ['record']),
                    'record' => $this->createTaskConfiguration('@test.record'),
                ]),
                'test.iterable_child' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.child_iterate', ['output' => ['a']]),
                ]),
            ],
            'stop'
        );

        $this->processManager = new ProcessManager(
            $container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $registry,
            new ContextualOptionResolver(),
            new EventDispatcher(),
            taskRegistry: new TaskRegistry($container),
        );
    }

    public function executeSubProcess(string $processCode): void
    {
        $this->processManager->execute($processCode);
    }

    public function record(mixed $value): void
    {
        $this->recorded[] = $value;
    }

    public function testABlockingTaskOfTheParentIsProceededAfterASubProcessWithATaskOfTheSameCode(): void
    {
        self::assertSame([1, 2, 3], $this->processManager->execute('test.blocking_parent'));
    }

    public function testAnIterableTaskOfTheParentIsFlushedAfterASubProcessWithATaskOfTheSameCode(): void
    {
        $this->processManager->execute('test.iterable_parent');

        self::assertSame([[1, 2], [3, 4], [5]], $this->recorded);
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     *
     * @return array<string, mixed>
     */
    private function createProcessConfiguration(array $tasks, ?string $endPoint = null): array
    {
        return [
            'options' => [],
            'entry_point' => null,
            'end_point' => $endPoint,
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
