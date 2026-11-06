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

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Event\ProcessEvent;
use CleverAge\ProcessBundle\Exception\ProcessFailedException;
use CleverAge\ProcessBundle\Logger\AbstractLogger;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * How the items flow between the tasks: skipped items, error outputs, stop of a process by a task, flush of the tasks
 * after an iterable task, empty iterable task, blocking tasks.
 *
 * The tasks record their calls in $this->events ("<task>:<call>:<input>").
 */
#[CoversClass(ProcessManager::class)]
#[UsesClass(ProcessFailedException::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(TaskRegistry::class)]
#[UsesClass(ContextualOptionResolver::class)]
#[UsesClass(ProcessEvent::class)]
#[UsesClass(AbstractLogger::class)]
#[UsesClass(ProcessHistory::class)]
#[UsesClass(ProcessState::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(DeprecatedCodeAliases::class)]
class ProcessManagerFlowTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $events = [];

    /**
     * @var list<bool>
     */
    private array $stoppedProcesses = [];

    /**
     * A failing item with the skip strategy: the next tasks are not executed for it, its error outputs receive its
     * input (default error output), the next items go on.
     */
    public function testSkipStrategy(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2, 3], 'outputs' => ['check']],
            'check' => ['fail_on' => 2, 'error_strategy' => 'skip', 'outputs' => ['after'], 'error_outputs' => ['errors']],
            'after' => [],
            'errors' => [],
        ]);

        self::assertSame(
            ['check:execute:1', 'after:execute:1', 'check:execute:2', 'errors:execute:2', 'check:execute:3', 'after:execute:3'],
            $this->getExecutions()
        );
    }

    /**
     * The error output set by the failing task is given to its error outputs, instead of its input.
     */
    public function testErrorOutputOfTheTask(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2], 'outputs' => ['check']],
            'check' => ['fail_on' => 2, 'error_output' => 'error 2', 'error_strategy' => 'skip', 'error_outputs' => ['errors']],
            'errors' => [],
        ]);

        self::assertSame(['check:execute:1', 'check:execute:2', 'errors:execute:error 2'], $this->getExecutions());
    }

    /**
     * A task stopping the process (ProcessState::stop(), without error): the next items of the parent iterable task
     * are not processed, the process ends normally (not flagged as stopped: only the stop task does it).
     */
    public function testStopIsPropagatedToTheParentTasks(): void
    {
        $result = $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2, 3], 'outputs' => ['check']],
            'check' => ['stop_on' => 2, 'outputs' => ['after']],
            'after' => [],
        ], ['end_point' => 'after']);

        self::assertSame(['check:execute:1', 'after:execute:1', 'check:execute:2'], $this->getExecutions());
        self::assertSame(1, $result, 'Output of the end point');
        self::assertSame([false], $this->stoppedProcesses);
    }

    /**
     * The stop is propagated through every parent task, up to the iterable task.
     */
    public function testStopIsPropagatedThroughTheParentTasks(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2, 3], 'outputs' => ['middle']],
            'middle' => ['outputs' => ['check']],
            'check' => ['stop_on' => 2],
        ]);

        self::assertSame(['middle:execute:1', 'check:execute:1', 'middle:execute:2', 'check:execute:2'], $this->getExecutions());
    }

    /**
     * The task of an error branch gets the state of the failing task, with its error.
     */
    public function testErrorBranchGetsThePreviousState(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2], 'outputs' => ['check']],
            'check' => ['fail_on' => 2, 'error_strategy' => 'skip', 'error_outputs' => ['errors']],
            'errors' => ['record_previous' => true],
        ]);

        self::assertContains('errors:previous:check:check failed', $this->events);
    }

    /**
     * The context of the process is given to the tasks.
     */
    public function testContext(): void
    {
        $this->createProcessManager(['single' => ['record_context' => true]])->execute('test.process', null, ['key' => 'value']);

        self::assertContains('single:context:{"key":"value"}', $this->events);
    }

    /**
     * A task of an error branch stopping the process.
     */
    public function testStopInAnErrorBranchIsPropagated(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2, 3], 'outputs' => ['check']],
            'check' => ['fail_on' => 2, 'error_strategy' => 'skip', 'error_outputs' => ['errors']],
            'errors' => ['stop_on' => 2],
        ]);

        self::assertSame(['check:execute:1', 'check:execute:2', 'errors:execute:2'], $this->getExecutions());
    }

    /**
     * Once an iterable task has no more item, its next tasks and their error outputs are flushed, down to the
     * blocking tasks. (Each task is flushed again with its next tasks once resolved: only the first flushes are
     * checked.).
     */
    public function testFlushAfterAnIterableTask(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2], 'outputs' => ['a']],
            'a' => ['type' => 'flushable', 'outputs' => ['b'], 'error_outputs' => ['c']],
            'b' => ['type' => 'flushable'],
            'c' => ['type' => 'flushable'],
        ]);

        self::assertSame(
            ['a:execute:1', 'b:execute:1', 'a:execute:2', 'b:execute:2', 'a:flush', 'b:flush', 'c:flush'],
            \array_slice($this->events, 0, 7)
        );
        self::assertSame([], array_diff(\array_slice($this->events, 7), ['a:flush', 'b:flush', 'c:flush']), 'Then only flushes');
    }

    /**
     * A task stopping the process when it is flushed after its iterable parent: its next tasks are not flushed then.
     */
    public function testStopDuringTheFlush(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1], 'outputs' => ['a']],
            'a' => ['type' => 'flushable', 'stop_on_flush' => true, 'outputs' => ['b']],
            'b' => ['type' => 'flushable'],
        ]);

        self::assertSame(['a:execute:1', 'b:execute:1', 'a:flush'], \array_slice($this->events, 0, 3));
        self::assertNotSame('b:flush', $this->events[3] ?? null);
    }

    /**
     * An iterable task without item: its next tasks are not executed.
     */
    public function testEmptyIterableTask(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [], 'outputs' => ['a']],
            'a' => ['type' => 'flushable', 'outputs' => ['b']],
            'b' => [],
        ]);

        self::assertSame([], $this->getExecutions());
    }

    /**
     * A blocking task receives every item, then gives them to its next tasks, which are not flushed (the flush stops at
     * the blocking task).
     */
    public function testBlockingTask(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2], 'outputs' => ['aggregate']],
            'aggregate' => ['type' => 'blocking', 'outputs' => ['after']],
            'after' => ['type' => 'flushable'],
        ]);

        // Not flushed before the blocking task is proceeded
        self::assertSame(['aggregate:execute:1', 'aggregate:execute:2', 'aggregate:proceed', 'after:execute:1,2'], \array_slice($this->events, 0, 4));
    }

    /**
     * A blocking task without input is not proceeded.
     */
    public function testBlockingTaskWithoutInput(): void
    {
        $this->execute([
            'items' => ['type' => 'iterable', 'items' => [], 'outputs' => ['aggregate']],
            'aggregate' => ['type' => 'blocking', 'outputs' => ['after']],
            'after' => [],
        ]);

        self::assertSame([], $this->events);
    }

    /**
     * A flushable end point: its flushes with nothing to output keep its last output, returned by the process (it
     * returned null).
     */
    public function testFlushableEndPoint(): void
    {
        $result = $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2], 'outputs' => ['last']],
            'last' => ['type' => 'flushable'],
        ], ['end_point' => 'last']);

        self::assertSame(['last:execute:1', 'last:execute:2', 'last:flush'], \array_slice($this->events, 0, 3));
        self::assertSame(2, $result);
    }

    /**
     * The process returns the last output passed on by its end point: the output of an execution whose output is
     * ignored (skipped, failed with the skip strategy) does not replace it (it was returned).
     *
     * @param array<string, mixed> $endPoint
     */
    #[DataProvider('provideEndPointsIgnoringTheLastItem')]
    public function testEndPointIgnoringItsLastItem(array $endPoint, mixed $expected): void
    {
        $result = $this->execute([
            'items' => ['type' => 'iterable', 'items' => [1, 2, 3], 'outputs' => ['last']],
            'last' => $endPoint,
        ], ['end_point' => 'last']);

        self::assertSame(['last:execute:1', 'last:execute:2', 'last:execute:3'], $this->getExecutions());
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, mixed}>
     */
    public static function provideEndPointsIgnoringTheLastItem(): iterable
    {
        yield 'skipped' => [['skip_on' => [3]], 2];
        yield 'failed with the skip strategy' => [['fail_on' => 3, 'error_strategy' => 'skip'], 2];
        yield 'every item skipped' => [['skip_on' => [1, 2, 3]], null];
    }

    /**
     * A task which is not iterable nor blocking is flushed once resolved.
     */
    public function testRootTaskIsFlushed(): void
    {
        $this->execute([
            'single' => ['type' => 'flushable', 'outputs' => ['after']],
            'after' => ['type' => 'flushable'],
        ]);

        self::assertSame(['single:execute:', 'after:execute:', 'single:flush', 'after:flush'], \array_slice($this->events, 0, 4));
    }

    /**
     * The process can be executed again: no item of the previous execution is left (e.g. after a failure).
     */
    public function testExecutedAgainAfterAFailure(): void
    {
        $tasks = [
            'items' => ['type' => 'iterable', 'items' => [1, 2], 'outputs' => ['aggregate']],
            'aggregate' => ['type' => 'blocking', 'outputs' => ['check']],
            'check' => ['fail_on' => '1,2'],
        ];
        $processManager = $this->createProcessManager($tasks);
        try {
            $processManager->execute('test.process');
            self::fail('The process should have failed');
        } catch (ProcessFailedException) {
        }
        $this->events = [];

        $this->expectException(ProcessFailedException::class);
        try {
            $processManager->execute('test.process');
        } finally {
            self::assertSame(['aggregate:execute:1', 'aggregate:execute:2', 'aggregate:proceed', 'check:execute:1,2'], $this->events);
        }
    }

    /**
     * @return list<string>
     */
    private function getExecutions(): array
    {
        return array_values(array_filter($this->events, static fn (string $event): bool => str_contains($event, ':execute:')));
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     * @param array<string, mixed>                $process
     */
    private function execute(array $tasks, array $process = []): mixed
    {
        return $this->createProcessManager($tasks, $process)->execute('test.process');
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     * @param array<string, mixed>                $process
     */
    private function createProcessManager(array $tasks, array $process = []): ProcessManager
    {
        $container = new Container();
        $taskConfigurations = [];
        foreach ($tasks as $code => $task) {
            $container->set('test.'.$code, $this->createTask($code, $task));
            $taskConfigurations[$code] = [
                'service' => '@test.'.$code,
                'options' => [],
                'description' => '',
                'help' => '',
                'outputs' => $task['outputs'] ?? [],
                'errors' => [],
                'error_outputs' => $task['error_outputs'] ?? [],
                'error_strategy' => $task['error_strategy'] ?? null,
                'log_level' => null,
            ];
        }
        $registry = new ProcessConfigurationRegistry([
            'test.process' => $process + [
                'options' => [],
                'entry_point' => null,
                'end_point' => null,
                'description' => '',
                'help' => '',
                'public' => true,
                'tasks' => $taskConfigurations,
            ],
        ], 'stop');
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ProcessEvent::EVENT_PROCESS_ENDED, function (ProcessEvent $event): void {
            $this->stoppedProcesses[] = $event->isProcessStopped();
        });
        $logger = new NullLogger();

        return new ProcessManager(
            $container,
            new ProcessLogger($logger),
            new TaskLogger($logger),
            $registry,
            new ContextualOptionResolver(),
            $eventDispatcher,
            taskRegistry: new TaskRegistry($container),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createTask(string $code, array $options): TaskInterface
    {
        $record = function (string $event): void {
            $this->events[] = $event;
        };
        $execute = static function (ProcessState $state) use ($code, $options, $record): void {
            $input = $state->getInput();
            $inputString = \is_array($input) ? implode(',', $input) : (string) $input;
            $record("{$code}:execute:{$inputString}");
            if ($options['record_previous'] ?? false) {
                $previousState = $state->getPreviousState();
                $record(\sprintf('%s:previous:%s:%s', $code, $previousState?->getTaskConfiguration()->getCode(), $previousState?->getException()?->getMessage()));
            }
            if ($options['record_context'] ?? false) {
                $record("{$code}:context:".json_encode($state->getContext()));
            }
            // skip_on: the items skipped, with an output (e.g. an empty value skipped by skip_empty)
            if (\in_array($input, $options['skip_on'] ?? [], true)) {
                $state->setOutput("skipped {$inputString}");
                $state->setSkipped(true);

                return;
            }
            if (isset($options['stop_on']) && $options['stop_on'] === $input) {
                $state->stop();

                return;
            }
            // fail_on: an item, or the imploded items received by the task
            if (isset($options['fail_on']) && \in_array($options['fail_on'], [$input, $inputString], true)) {
                if (isset($options['error_output'])) {
                    $state->setErrorOutput($options['error_output']);
                }

                throw new \RuntimeException("{$code} failed");
            }
            $state->setOutput($input);
        };

        return match ($options['type'] ?? null) {
            'iterable' => new class($options['items']) implements IterableTaskInterface {
                private int $position = 0;

                /**
                 * @param list<mixed> $items
                 */
                public function __construct(private readonly array $items)
                {
                }

                public function execute(ProcessState $state): void
                {
                    if ([] === $this->items) {
                        $state->setSkipped(true);

                        return;
                    }
                    $state->setOutput($this->items[$this->position]);
                }

                public function next(ProcessState $state): bool
                {
                    ++$this->position;
                    if ($this->position >= \count($this->items)) {
                        $this->position = 0;

                        return false;
                    }

                    return true;
                }
            },
            'flushable' => new class($execute, $record, $code, $options['stop_on_flush'] ?? false) implements FlushableTaskInterface {
                public function __construct(
                    private readonly \Closure $execute,
                    private readonly \Closure $record,
                    private readonly string $code,
                    private readonly bool $stopOnFlush,
                ) {
                }

                public function execute(ProcessState $state): void
                {
                    ($this->execute)($state);
                }

                public function flush(ProcessState $state): void
                {
                    ($this->record)("{$this->code}:flush");
                    if ($this->stopOnFlush) {
                        $state->stop();

                        return;
                    }
                    $state->setSkipped(true);
                }
            },
            'blocking' => new class($record, $code) implements BlockingTaskInterface {
                /**
                 * @var list<mixed>
                 */
                private array $inputs = [];

                public function __construct(
                    private readonly \Closure $record,
                    private readonly string $code,
                ) {
                }

                public function execute(ProcessState $state): void
                {
                    ($this->record)("{$this->code}:execute:{$state->getInput()}");
                    $this->inputs[] = $state->getInput();
                }

                public function proceed(ProcessState $state): void
                {
                    ($this->record)("{$this->code}:proceed");
                    $state->setOutput($this->inputs);
                    $this->inputs = [];
                }
            },
            default => new class($execute) implements TaskInterface {
                public function __construct(private readonly \Closure $execute)
                {
                }

                public function execute(ProcessState $state): void
                {
                    ($this->execute)($state);
                }
            },
        };
    }
}
