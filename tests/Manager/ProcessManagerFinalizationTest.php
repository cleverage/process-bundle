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
use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Logger\AbstractLogger;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\InitializableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The initialized tasks are finalized once, whether the process succeeds or fails.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessFailedException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractLogger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfigurationRegistry::class)]
class ProcessManagerFinalizationTest extends TestCase
{
    private Container $container;

    private EventDispatcher $eventDispatcher;

    private ProcessManager $processManager;

    /**
     * The finalized tasks, with the task configuration seen by the logs during their finalization.
     *
     * @var list<string>
     */
    private array $finalized = [];

    /**
     * Whether the process history was failed during each finalization.
     *
     * @var list<bool>
     */
    private array $failedAtFinalization = [];

    /**
     * The logs of the tasks, with the task configuration seen by the logs.
     *
     * @var list<array{string, string, ?string}>
     */
    private array $taskLogs = [];

    /**
     * A message of the process logs that throws an exception when logged.
     */
    private ?string $failingProcessLog = null;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->set('test.noop', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
            }
        });
        $this->container->set('test.failing', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
                throw new \LogicException('Something went wrong');
            }
        });
        // Runs the given sub-process, as the process_executor task
        $this->container->set('test.sub_process', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerFinalizationTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->executeSubProcess((string) $state->getTaskConfiguration()->getOption('process'));
            }
        });
        foreach (['first', 'second', 'third', 'child'] as $code) {
            $this->container->set("test.finalizable_{$code}", $this->createFinalizableTask($code));
        }
        $this->container->set('test.finalizable_throwing', $this->createFinalizableTask('throwing', failFinalization: true));
        $this->container->set('test.finalizable_bad_init', $this->createFinalizableTask('bad_init', failInitialization: true));

        $this->eventDispatcher = new EventDispatcher();
    }

    public function executeSubProcess(string $processCode): void
    {
        $this->processManager->execute($processCode);
    }

    public function testTheTasksAreFinalizedWhenTheProcessSucceeds(): void
    {
        $this->execute([
            'first' => $this->createTaskConfiguration('@test.finalizable_first', ['second']),
            'second' => $this->createTaskConfiguration('@test.finalizable_second'),
        ]);

        self::assertSame(['first (first)', 'second (second)'], $this->finalized);
        self::assertSame([false, false], $this->failedAtFinalization);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    public function testTheTasksAreFinalizedWhenTheProcessFails(): void
    {
        $failingTask = null;
        $this->eventDispatcher->addListener(ProcessEvent::EVENT_PROCESS_FAILED, function () use (&$failingTask): void {
            $failingTask = $this->processManager->getTaskConfiguration()?->getCode();
        });

        $error = $this->executeFailing([
            'first' => $this->createTaskConfiguration('@test.finalizable_first', ['fail']),
            'fail' => $this->createTaskConfiguration('@test.failing', ['second']),
            // Initialized, never executed
            'second' => $this->createTaskConfiguration('@test.finalizable_second'),
        ]);

        self::assertInstanceOf(ProcessFailedException::class, $error);
        self::assertSame('Something went wrong', $error->getPrevious()?->getMessage());
        // The logs of each finalization are attributed to the finalized task
        self::assertSame(['first (first)', 'second (second)'], $this->finalized);
        // The tasks can tell the process has failed
        self::assertSame([true, true], $this->failedAtFinalization);
        // The failing task is kept for the logs of the failure
        self::assertSame('fail', $failingTask);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    public function testATaskWhoseInitializationHasFailedIsNotFinalizedWhenTheProcessFails(): void
    {
        set_error_handler(static fn (): bool => true, \E_USER_DEPRECATED);
        try {
            $error = $this->executeFailing([
                'first' => $this->createTaskConfiguration('@test.finalizable_first', ['bad_init']),
                'bad_init' => $this->createTaskConfiguration('@test.finalizable_bad_init'),
            ]);
        } finally {
            restore_error_handler();
        }

        self::assertInstanceOf(ProcessFailedException::class, $error);
        self::assertSame('Invalid configuration', $error->getPrevious()?->getMessage());
        self::assertSame(['first (first)'], $this->finalized);
    }

    public function testTheTasksThatAreNotInitializedAreNotFinalizedWhenTheProcessFails(): void
    {
        $error = $this->executeFailing([
            'first' => $this->createTaskConfiguration('@test.finalizable_first', ['missing']),
            'missing' => $this->createTaskConfiguration('test.missing', ['second']),
            'second' => $this->createTaskConfiguration('@test.finalizable_second'),
        ]);

        self::assertInstanceOf(UnexpectedValueException::class, $error);
        self::assertSame(['first (first)'], $this->finalized);
        // Also failed for an exception thrown outside a task
        self::assertSame([true], $this->failedAtFinalization);
    }

    public function testAFinalizationFailureIsLoggedAndDoesNotHideTheErrorOfTheProcess(): void
    {
        $error = $this->executeFailing([
            'throwing' => $this->createTaskConfiguration('@test.finalizable_throwing', ['fail']),
            'fail' => $this->createTaskConfiguration('@test.failing'),
            'second' => $this->createTaskConfiguration('@test.finalizable_second'),
        ]);

        self::assertInstanceOf(ProcessFailedException::class, $error);
        self::assertSame('Something went wrong', $error->getPrevious()?->getMessage());
        // The next tasks are still finalized
        self::assertSame(['throwing (throwing)', 'second (second)'], $this->finalized);
        self::assertContains(['critical', 'Finalization failed', 'throwing'], $this->taskLogs);
    }

    public function testTheTasksAreNotFinalizedTwiceWhenTheProcessFailsAfterTheirFinalization(): void
    {
        $this->failingProcessLog = 'Process test.process succeed';

        $error = $this->executeFailing([
            'first' => $this->createTaskConfiguration('@test.finalizable_first', ['second']),
            'second' => $this->createTaskConfiguration('@test.finalizable_second'),
        ]);

        self::assertInstanceOf(\RuntimeException::class, $error);
        self::assertSame('Logger failure', $error->getMessage());
        self::assertSame(['first (first)', 'second (second)'], $this->finalized);
    }

    public function testAFailedSubProcessIsFinalizedAndItsParentGoesOn(): void
    {
        $this->createProcessManager([
            'test.process' => [
                'first' => $this->createTaskConfiguration('@test.finalizable_first', ['sub_process']),
                'sub_process' => $this->createTaskConfiguration('@test.sub_process', ['second'], ['process' => 'test.child'], 'skip'),
                'second' => $this->createTaskConfiguration('@test.finalizable_second'),
            ],
            'test.child' => [
                'child' => $this->createTaskConfiguration('@test.finalizable_child', ['fail']),
                'fail' => $this->createTaskConfiguration('@test.failing'),
            ],
        ]);

        $this->processManager->execute('test.process');

        // The task of the sub-process is finalized when it fails, the tasks of the parent when it ends
        self::assertSame(['child (child)', 'first (first)', 'second (second)'], $this->finalized);
        self::assertNull($this->processManager->getTaskConfiguration());
    }

    public function record(string $code, bool $failed): void
    {
        $this->failedAtFinalization[] = $failed;
        $this->finalized[] = "{$code} ({$this->processManager->getTaskConfiguration()?->getCode()})";
    }

    private function createFinalizableTask(
        string $code,
        bool $failFinalization = false,
        bool $failInitialization = false,
    ): TaskInterface {
        return new class($this, $code, $failFinalization, $failInitialization) implements InitializableTaskInterface, FinalizableTaskInterface {
            public function __construct(
                private readonly ProcessManagerFinalizationTest $test,
                private readonly string $code,
                private readonly bool $failFinalization,
                private readonly bool $failInitialization,
            ) {
            }

            public function initialize(ProcessState $state): void
            {
                if ($this->failInitialization) {
                    throw new \InvalidArgumentException('Invalid configuration');
                }
            }

            /**
             * Like configurable tasks (options are resolved again), fail again when executed.
             */
            public function execute(ProcessState $state): void
            {
                $this->initialize($state);
                $state->setOutput($state->getInput());
            }

            public function finalize(ProcessState $state): void
            {
                $this->test->record($this->code, $state->getProcessHistory()->isFailed());
                if ($this->failFinalization) {
                    throw new \RuntimeException('Finalization failed');
                }
            }
        };
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     */
    private function execute(array $tasks): void
    {
        $this->createProcessManager(['test.process' => $tasks]);
        $this->processManager->execute('test.process');
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     */
    private function executeFailing(array $tasks): \Throwable
    {
        try {
            $this->execute($tasks);
        } catch (\Throwable $error) {
            return $error;
        }

        self::fail('The process should have failed');
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $processes
     */
    private function createProcessManager(array $processes): void
    {
        $configurations = [];
        foreach ($processes as $code => $tasks) {
            $configurations[$code] = [
                'options' => [],
                'entry_point' => null,
                'end_point' => null,
                'description' => '',
                'help' => '',
                'public' => true,
                'tasks' => $tasks,
            ];
        }

        $test = $this;
        $processLogger = new class($test) extends PsrAbstractLogger {
            public function __construct(
                private readonly ProcessManagerFinalizationTest $test,
            ) {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->test->logProcess((string) $message);
            }
        };
        $taskLogger = new class($test) extends PsrAbstractLogger {
            public function __construct(
                private readonly ProcessManagerFinalizationTest $test,
            ) {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->test->logTask((string) $level, (string) $message);
            }
        };

        $this->processManager = new ProcessManager(
            $this->container,
            new ProcessLogger($processLogger),
            new TaskLogger($taskLogger),
            new ProcessConfigurationRegistry($configurations, 'stop'),
            new ContextualOptionResolver(),
            $this->eventDispatcher,
            taskRegistry: new TaskRegistry($this->container),
        );
    }

    public function logProcess(string $message): void
    {
        if ($message === $this->failingProcessLog) {
            throw new \RuntimeException('Logger failure');
        }
    }

    public function logTask(string $level, string $message): void
    {
        $this->taskLogs[] = [$level, $message, $this->processManager->getTaskConfiguration()?->getCode()];
    }

    /**
     * @param list<string>         $outputs
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function createTaskConfiguration(
        string $service,
        array $outputs = [],
        array $options = [],
        ?string $errorStrategy = null,
    ): array {
        return [
            'service' => $service,
            'options' => $options,
            'description' => '',
            'help' => '',
            'outputs' => $outputs,
            'errors' => [],
            'error_outputs' => [],
            'error_strategy' => $errorStrategy,
            'log_level' => null,
        ];
    }
}
