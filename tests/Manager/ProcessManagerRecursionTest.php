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
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use CleverAge\ProcessBundle\Task\AggregateIterableTask;
use CleverAge\ProcessBundle\Task\ConstantIterableOutputTask;
use CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask;
use CleverAge\ProcessBundle\Task\StopTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * A process has only one configuration (task instances and states): executing it while it is running (directly or
 * through sub-processes) resets them, which is deprecated. Executing the same sub-process several times in a row, or a
 * process again once it has ended, is not concerned.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessFailedException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractLogger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfigurationRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ConstantIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AggregateIterableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessExecutorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(StopTask::class)]
class ProcessManagerRecursionTest extends TestCase
{
    private ProcessManager $processManager;

    private EventDispatcher $eventDispatcher;

    /**
     * Number of sub-processes the recursive task may still execute (bounded recursion).
     */
    private int $recursions = 0;

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    protected function setUp(): void
    {
        $registry = new ProcessConfigurationRegistry(
            [
                'test.recursive' => $this->createProcessConfiguration([
                    'recurse' => $this->createTaskConfiguration('@test.recurse', ['process' => 'test.recursive']),
                ], 'recurse'),
                'test.a' => $this->createProcessConfiguration([
                    'recurse' => $this->createTaskConfiguration('@test.recurse', ['process' => 'test.b']),
                ], 'recurse'),
                'test.b' => $this->createProcessConfiguration([
                    'recurse' => $this->createTaskConfiguration('@test.recurse', ['process' => 'test.a']),
                ], 'recurse'),
                'test.parent' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.parent.iterate', ['output' => [1, 2, 3]], ['execute']),
                    'execute' => $this->createTaskConfiguration('@test.parent.execute', ['process' => 'test.child'], ['aggregate']),
                    'aggregate' => $this->createTaskConfiguration('@test.parent.aggregate'),
                ], 'aggregate'),
                'test.child' => $this->createProcessConfiguration([
                    'echo' => $this->createTaskConfiguration('@test.child.echo'),
                ], 'echo', 'echo'),
                'test.failing' => $this->createProcessConfiguration([
                    'fail' => $this->createTaskConfiguration('@test.failing.fail'),
                ]),
                'test.stopped' => $this->createProcessConfiguration([
                    'stop' => $this->createTaskConfiguration('@test.stopped.stop'),
                ]),
            ],
            'stop'
        );

        $container = new Container();
        $this->eventDispatcher = new EventDispatcher();
        $this->processManager = new ProcessManager(
            $container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $registry,
            new ContextualOptionResolver(),
            $this->eventDispatcher,
            taskRegistry: new TaskRegistry($container),
        );

        // Executes the given process while recursions are allowed, outputs the number of nested executions
        $container->set('test.recurse', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerRecursionTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $state->setOutput($this->test->recurse((string) $state->getTaskConfiguration()->getOption('process')));
            }
        });
        $container->set('test.parent.iterate', new ConstantIterableOutputTask());
        $container->set('test.parent.execute', new ProcessExecutorTask($this->processManager, $registry, new NullLogger()));
        $container->set('test.parent.aggregate', new AggregateIterableTask());
        $container->set('test.child.echo', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
                $state->setOutput("child {$state->getInput()}");
            }
        });
        $container->set('test.failing.fail', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
                throw new \RuntimeException('Failure');
            }
        });
        $container->set('test.stopped.stop', new StopTask());

        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function recurse(string $processCode): int
    {
        if ($this->recursions <= 0) {
            return 0;
        }
        --$this->recursions;

        return 1 + (int) $this->processManager->execute($processCode);
    }

    public function testAProcessExecutingItselfIsDeprecated(): void
    {
        $this->recursions = 2;

        // The execution goes on as before: the states of the running process are reset, its output is the deepest one
        self::assertSame(0, $this->processManager->execute('test.recursive'));
        self::assertSame(0, $this->recursions);
        self::assertSame([
            $this->getExpectedDeprecation('test.recursive', 'test.recursive -> test.recursive'),
            $this->getExpectedDeprecation('test.recursive', 'test.recursive -> test.recursive -> test.recursive'),
        ], $this->deprecations);
    }

    public function testAProcessExecutedAgainThroughASubProcessIsDeprecated(): void
    {
        $this->recursions = 2;

        self::assertSame(0, $this->processManager->execute('test.a'));
        self::assertSame(0, $this->recursions);
        self::assertSame([
            $this->getExpectedDeprecation('test.a', 'test.a -> test.b -> test.a'),
        ], $this->deprecations);
    }

    public function testTheSameSubProcessExecutedSeveralTimesInARowIsNotDeprecated(): void
    {
        self::assertSame(['child 1', 'child 2', 'child 3'], $this->processManager->execute('test.parent'));
        self::assertSame([], $this->deprecations);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideListenerEvents(): iterable
    {
        yield 'start' => [ProcessEvent::EVENT_PROCESS_STARTED, 'test.child'];
        yield 'end' => [ProcessEvent::EVENT_PROCESS_ENDED, 'test.child'];
        yield 'fail' => [ProcessEvent::EVENT_PROCESS_FAILED, 'test.failing'];
    }

    #[DataProvider('provideListenerEvents')]
    public function testAListenerExecutingTheProcessAgainIsNotDeprecated(string $eventName, string $processCode): void
    {
        $executions = 0;
        $this->eventDispatcher->addListener($eventName, function () use ($processCode, &$executions): void {
            if ($executions++ > 0) {
                return;
            }
            try {
                $this->processManager->execute($processCode, 0);
            } catch (ProcessFailedException) {
            }
        });

        try {
            $this->processManager->execute($processCode, 0);
        } catch (ProcessFailedException) {
        }

        self::assertSame(2, $executions);
        self::assertSame([], $this->deprecations);
    }

    public function testAProcessExecutedAgainAfterAFailureIsNotDeprecated(): void
    {
        for ($i = 0; $i < 2; ++$i) {
            try {
                $this->processManager->execute('test.failing');
                self::fail('No exception thrown');
            } catch (ProcessFailedException $exception) {
                self::assertSame('Failure', $exception->getPrevious()?->getMessage());
            }
        }
        self::assertSame([], $this->deprecations);
    }

    public function testAProcessExecutedAgainAfterAStopIsNotDeprecated(): void
    {
        self::assertNull($this->processManager->execute('test.stopped'));
        self::assertNull($this->processManager->execute('test.stopped'));
        self::assertSame([], $this->deprecations);
    }

    public function testResetForgetsTheRunningProcesses(): void
    {
        // As if the execution of a process had been interrupted
        (new \ReflectionProperty(ProcessManager::class, 'runningProcessCodes'))->setValue($this->processManager, ['test.child']);
        $this->processManager->reset();

        self::assertSame('child 0', $this->processManager->execute('test.child', 0));
        self::assertSame([], $this->deprecations);
    }

    private function getExpectedDeprecation(string $processCode, string $chain): string
    {
        return "Since cleverage/process-bundle 5.6: Executing the process \"{$processCode}\" while it is already running ({$chain}) is deprecated: it resets the states and task instances of the running process. In v6.0, an exception will be thrown.";
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     *
     * @return array<string, mixed>
     */
    private function createProcessConfiguration(array $tasks, ?string $endPoint = null, ?string $entryPoint = null): array
    {
        return [
            'options' => [],
            'entry_point' => $entryPoint,
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
