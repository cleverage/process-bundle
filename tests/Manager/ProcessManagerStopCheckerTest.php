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
use CleverAge\ProcessBundle\Exception\ProcessStoppedException;
use CleverAge\ProcessBundle\Logger\AbstractLogger;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\ProcessStopCheckerInterface;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use CleverAge\ProcessBundle\Task\ConstantIterableOutputTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ProcessManager::class)]
#[CoversClass(ProcessStoppedException::class)]
#[UsesClass(ProcessFailedException::class)]
#[UsesClass(TaskRegistry::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ContextualOptionResolver::class)]
#[UsesClass(ProcessEvent::class)]
#[UsesClass(AbstractLogger::class)]
#[UsesClass(AbstractConfigurableTask::class)]
#[UsesClass(AbstractIterableOutputTask::class)]
#[UsesClass(ProcessHistory::class)]
#[UsesClass(ProcessState::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(ConstantIterableOutputTask::class)]
class ProcessManagerStopCheckerTest extends TestCase
{
    /**
     * @var list<mixed>
     */
    private array $recorded = [];

    private ?\Throwable $failedEventError = null;

    private ?ProcessManager $processManager = null;

    /**
     * Whether the history of the process manager was failed when the "failed" event was dispatched.
     */
    private ?bool $failedEventHistoryIsFailed = null;

    public function testWithoutStopCheckerTheProcessRunsEntirely(): void
    {
        $this->createProcessManager([])->execute('test.process');

        self::assertSame([1, 2, 3, 4], $this->recorded);
        self::assertNull($this->failedEventError);
    }

    public function testCheckerNotRequestingAStop(): void
    {
        $checked = [];
        $checker = $this->createChecker(static function (ProcessState $state) use (&$checked): bool {
            $checked[] = $state->getTaskConfiguration()->getCode();

            return false;
        });

        $this->createProcessManager([$checker])->execute('test.process');

        self::assertSame([1, 2, 3, 4], $this->recorded);
        // Before each task execution: once per item for the iterable task and its children
        self::assertSame(['iterate', 'record', 'iterate', 'record', 'iterate', 'record', 'iterate', 'record'], $checked);
    }

    /**
     * The stop is not handled as a task error: the "skip" error strategy of the task does not apply, its error
     * outputs are not executed.
     */
    public function testStopRequestedDuringTheIteration(): void
    {
        $checker = $this->createChecker(static fn (ProcessState $state): bool => 'record' === $state->getTaskConfiguration()->getCode() && 3 === $state->getInput());
        $processManager = $this->createProcessManager([$checker]);

        try {
            $processManager->execute('test.process');
            self::fail('The process should have failed');
        } catch (ProcessFailedException $exception) {
            $stopped = $exception->getPrevious();
            self::assertInstanceOf(ProcessStoppedException::class, $stopped);
            self::assertSame('Process test.process has been stopped before task record.', $stopped->getMessage());
        }

        self::assertSame([1, 2], $this->recorded);
        self::assertSame($exception, $this->failedEventError);
        self::assertTrue($this->failedEventHistoryIsFailed);
        // The history of the failed process is not kept once it has ended
        self::assertNull($processManager->getProcessHistory());
    }

    public function testAnyCheckerCanRequestAStop(): void
    {
        $neverStops = $this->createChecker(static fn (): bool => false);
        $alwaysStops = $this->createChecker(static fn (): bool => true);

        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage('Process test.process has been stopped before task iterate.');

        try {
            $this->createProcessManager([$neverStops, $alwaysStops])->execute('test.process');
        } finally {
            self::assertSame([], $this->recorded);
        }
    }

    /**
     * @param \Closure(ProcessState): bool $shouldStop
     */
    private function createChecker(\Closure $shouldStop): ProcessStopCheckerInterface
    {
        return new class($shouldStop) implements ProcessStopCheckerInterface {
            /**
             * @param \Closure(ProcessState): bool $shouldStop
             */
            public function __construct(private readonly \Closure $shouldStop)
            {
            }

            public function shouldStop(ProcessState $state): bool
            {
                return ($this->shouldStop)($state);
            }
        };
    }

    /**
     * @param list<ProcessStopCheckerInterface> $stopCheckers
     */
    private function createProcessManager(array $stopCheckers): ProcessManager
    {
        $recordTask = new class(function (mixed $input): void {
            $this->recorded[] = $input;
        }) implements TaskInterface {
            /**
             * @param \Closure(mixed): void $record
             */
            public function __construct(private readonly \Closure $record)
            {
            }

            public function execute(ProcessState $state): void
            {
                ($this->record)($state->getInput());
            }
        };

        $container = new Container();
        $container->set('test.iterate', new ConstantIterableOutputTask());
        $container->set('test.record', $recordTask);

        $registry = new ProcessConfigurationRegistry(
            [
                'test.process' => [
                    'options' => [],
                    'entry_point' => null,
                    'end_point' => null,
                    'description' => '',
                    'help' => '',
                    'public' => true,
                    'tasks' => [
                        'iterate' => $this->createTaskConfiguration('@test.iterate', ['output' => [1, 2, 3, 4]], ['record']),
                        'record' => $this->createTaskConfiguration('@test.record', [], [], 'skip', ['error']),
                        'error' => $this->createTaskConfiguration('@test.record'),
                    ],
                ],
            ],
            'stop'
        );

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ProcessEvent::EVENT_PROCESS_FAILED, function (ProcessEvent $event): void {
            $this->failedEventError = $event->getProcessError();
            $this->failedEventHistoryIsFailed = $this->processManager?->getProcessHistory()?->isFailed();
        });

        return $this->processManager = new ProcessManager(
            $container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $registry,
            new ContextualOptionResolver(),
            $eventDispatcher,
            $stopCheckers,
            taskRegistry: new TaskRegistry($container),
        );
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string>         $outputs
     * @param list<string>         $errorOutputs
     *
     * @return array<string, mixed>
     */
    private function createTaskConfiguration(
        string $service,
        array $options = [],
        array $outputs = [],
        ?string $errorStrategy = null,
        array $errorOutputs = [],
    ): array {
        return [
            'service' => $service,
            'options' => $options,
            'description' => '',
            'help' => '',
            'outputs' => $outputs,
            'errors' => [],
            'error_outputs' => $errorOutputs,
            'error_strategy' => $errorStrategy,
            'log_level' => null,
        ];
    }
}
