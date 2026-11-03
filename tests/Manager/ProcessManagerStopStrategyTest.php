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
use CleverAge\ProcessBundle\Task\ConstantOutputTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessFailedException::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(ConstantOutputTask::class)]
class ProcessManagerStopStrategyTest extends TestCase
{
    /**
     * @var list<array{string, string, array<string, mixed>}>
     */
    private array $records = [];

    /**
     * @var list<mixed>
     */
    private array $errorBranchInputs = [];

    public function testStopStrategyThrowsAProcessFailedExceptionWithTheOriginalException(): void
    {
        $originalException = new \LogicException('Something went wrong', 42);
        $processManager = $this->createProcessManager($originalException);

        try {
            $processManager->execute('test.process');
            self::fail('The process should have failed');
        } catch (\Exception $exception) { // An \Exception, not an \Error: catchable with catch (\Exception)
            self::assertInstanceOf(ProcessFailedException::class, $exception);
            self::assertSame(
                "Process test.process has failed during process fail with message: 'Something went wrong'.\n",
                $exception->getMessage()
            );
            self::assertSame($originalException, $exception->getPrevious());
        }
    }

    public function testSkipStrategyDoesNotThrow(): void
    {
        $processManager = $this->createProcessManager(new \LogicException('Something went wrong'), 'skip');

        self::assertNull($processManager->execute('test.process'));
    }

    /**
     * The error branch of a task with the stop strategy is executed before the process stops (a debug message said it
     * would never be reached).
     */
    public function testStopStrategyExecutesTheErrorBranchBeforeStopping(): void
    {
        $processManager = $this->createProcessManager(new \LogicException('Something went wrong'), errorOutputs: ['error_branch']);

        try {
            $processManager->execute('test.process');
            self::fail('The process should have failed');
        } catch (ProcessFailedException) {
        }

        self::assertSame(['value'], $this->errorBranchInputs);
        foreach ($this->records as [, $message]) {
            self::assertStringNotContainsString('never be reached', $message);
        }
    }

    /**
     * An error thrown by a listener of the process failure is logged, the error of the process is still thrown (it was
     * replaced by the error of the listener).
     */
    public function testAFailureListenerErrorDoesNotReplaceTheErrorOfTheProcess(): void
    {
        $originalException = new \LogicException('Something went wrong');
        $eventDispatcher = new EventDispatcher();
        $failedEvents = [];
        $eventDispatcher->addListener(ProcessEvent::EVENT_PROCESS_FAILED, static function () use (&$failedEvents): void {
            $failedEvents[] = 'first';

            throw new \RuntimeException('Listener error');
        });
        $processManager = $this->createProcessManager($originalException, eventDispatcher: $eventDispatcher);

        try {
            $processManager->execute('test.process');
            self::fail('The process should have failed');
        } catch (\Throwable $exception) {
            self::assertInstanceOf(ProcessFailedException::class, $exception);
            self::assertSame($originalException, $exception->getPrevious());
        }

        self::assertSame(['first'], $failedEvents);
        $errors = array_values(array_filter(
            $this->records,
            static fn (array $record): bool => LogLevel::ERROR === $record[0]
        ));
        self::assertCount(1, $errors);
        self::assertSame('A listener of the process failure has failed', $errors[0][1]);
        self::assertSame('Listener error', $errors[0][2]['error']);
    }

    /**
     * @param list<string> $errorOutputs
     */
    private function createProcessManager(
        \Throwable $exception,
        string $errorStrategy = 'stop',
        array $errorOutputs = [],
        EventDispatcher $eventDispatcher = new EventDispatcher(),
    ): ProcessManager {
        $errorBranchTask = new class(function (mixed $input): void {
            $this->errorBranchInputs[] = $input;
        }) implements TaskInterface {
            public function __construct(
                private readonly \Closure $onExecute,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                ($this->onExecute)($state->getInput());
            }
        };
        $logger = new class(function (string $level, string $message, array $context): void {
            $this->records[] = [$level, $message, $context];
        }) extends PsrAbstractLogger {
            public function __construct(
                private readonly \Closure $onLog,
            ) {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ($this->onLog)((string) $level, (string) $message, $context);
            }
        };

        $failingTask = new class($exception) implements TaskInterface {
            public function __construct(
                private readonly \Throwable $exception,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                throw $this->exception;
            }
        };

        $container = new Container();
        $container->set('test.constant', new ConstantOutputTask());
        $container->set('test.failing', $failingTask);
        $container->set('test.error_branch', $errorBranchTask);

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
                        'entry' => $this->createTaskConfiguration('@test.constant', ['output' => 'value'], ['fail']),
                        'fail' => $this->createTaskConfiguration('@test.failing', [], [], $errorStrategy, $errorOutputs),
                        'error_branch' => $this->createTaskConfiguration('@test.error_branch'),
                    ],
                ],
            ],
            'stop'
        );

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
