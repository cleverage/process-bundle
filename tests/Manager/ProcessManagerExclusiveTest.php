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
use CleverAge\ProcessBundle\Exception\LogicException;
use CleverAge\ProcessBundle\Exception\ProcessAlreadyRunningException;
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
use CleverAge\ProcessBundle\Task\ConstantIterableOutputTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\InMemoryStore;

#[CoversClass(ProcessManager::class)]
#[CoversClass(ProcessAlreadyRunningException::class)]
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
class ProcessManagerExclusiveTest extends TestCase
{
    /**
     * @var list<mixed>
     */
    private array $recorded = [];

    /**
     * @var list<\Throwable|null>
     */
    private array $failedEventErrors = [];

    /**
     * Called by the record task for each item.
     *
     * @var (\Closure(mixed): void)|null
     */
    private ?\Closure $onRecord = null;

    /**
     * Another execution of an exclusive process, e.g. from another PHP process sharing the lock store, fails while
     * the first one is running; the first one goes on.
     */
    public function testExclusiveProcessFailsWhileItIsRunning(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());
        $other = $this->createProcessManager($lockFactory);
        $otherError = null;
        $this->onRecord = static function (mixed $item) use ($other, &$otherError): void {
            if (2 !== $item) {
                return;
            }
            try {
                $other->execute('test.exclusive');
            } catch (\Throwable $error) {
                $otherError = $error;
            }
        };

        $this->createProcessManager($lockFactory)->execute('test.exclusive');

        self::assertInstanceOf(ProcessAlreadyRunningException::class, $otherError);
        self::assertSame('Process test.exclusive is exclusive and is already running: it is not executed.', $otherError->getMessage());
        // Dispatched as any failure, e.g. recorded as a failed execution by a user interface
        self::assertSame([$otherError], $this->failedEventErrors);
        self::assertSame([1, 2, 3], $this->recorded, 'The running execution goes on');
    }

    /**
     * The lock is released at the end of the process, succeeded or failed.
     */
    public function testLockIsReleasedAtTheEndOfTheProcess(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());
        $processManager = $this->createProcessManager($lockFactory);

        $processManager->execute('test.exclusive');
        $processManager->execute('test.exclusive');
        self::assertSame([1, 2, 3, 1, 2, 3], $this->recorded);

        $this->onRecord = static function (): void {
            throw new \RuntimeException('Task failure');
        };
        try {
            $processManager->execute('test.exclusive');
            self::fail('The process should have failed');
        } catch (ProcessFailedException) {
        }
        $this->onRecord = null;

        $lock = $lockFactory->createLock(ProcessManager::LOCK_PREFIX.'test.exclusive');
        self::assertTrue($lock->acquire(), 'Released after the failure');
        $lock->release();
    }

    public function testProcessNotExclusiveDoesNotNeedTheLockFactory(): void
    {
        $this->createProcessManager(null)->execute('test.shared');

        self::assertSame([1, 2, 3], $this->recorded);
    }

    public function testExclusiveProcessWithoutLockFactory(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The process test.exclusive is exclusive: it requires the lock.factory service of symfony/lock');

        try {
            $this->createProcessManager(null)->execute('test.exclusive');
        } finally {
            self::assertSame([], $this->recorded);
            self::assertCount(1, $this->failedEventErrors);
        }
    }

    /**
     * The lock is refreshed before a task when it has lived more than half of its TTL.
     */
    public function testLockIsRefreshedBeforeTheTasks(): void
    {
        $lifetimes = [ProcessManager::LOCK_TTL, ProcessManager::LOCK_TTL / 2 - 1, ProcessManager::LOCK_TTL / 2 + 1];
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->method('getRemainingLifetime')->willReturnCallback(static function () use (&$lifetimes): float {
            return (float) (array_shift($lifetimes) ?? ProcessManager::LOCK_TTL);
        });
        // Once, before the second task execution
        $lock->expects(self::once())->method('refresh');
        $lock->expects(self::once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')
            ->with(ProcessManager::LOCK_PREFIX.'test.exclusive', ProcessManager::LOCK_TTL)
            ->willReturn($lock);

        $this->createProcessManager($lockFactory)->execute('test.exclusive');
    }

    /**
     * The locks of the processes interrupted without ending (e.g. a fatal error caught by a long-running worker) are
     * released by the reset of the services.
     */
    public function testResetReleasesTheLocks(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());
        $processManager = $this->createProcessManager($lockFactory);
        $this->onRecord = static function () use ($processManager): void {
            $processManager->reset();
        };

        $processManager->execute('test.exclusive');

        $lock = $lockFactory->createLock(ProcessManager::LOCK_PREFIX.'test.exclusive');
        self::assertTrue($lock->acquire());
    }

    private function createProcessManager(?LockFactory $lockFactory): ProcessManager
    {
        $recordTask = new class(function (mixed $input): void {
            $this->recorded[] = $input;
            if ($this->onRecord instanceof \Closure) {
                ($this->onRecord)($input);
            }
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

        $tasks = [
            'iterate' => $this->createTaskConfiguration('@test.iterate', ['output' => [1, 2, 3]], ['record']),
            'record' => $this->createTaskConfiguration('@test.record'),
        ];
        $registry = new ProcessConfigurationRegistry(
            [
                'test.exclusive' => $this->createProcess($tasks, true),
                'test.shared' => $this->createProcess($tasks, false),
            ],
            'stop'
        );

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ProcessEvent::EVENT_PROCESS_FAILED, function (ProcessEvent $event): void {
            $this->failedEventErrors[] = $event->getProcessError();
        });

        return new ProcessManager(
            $container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $registry,
            new ContextualOptionResolver(),
            $eventDispatcher,
            [],
            new TaskRegistry($container),
            $lockFactory,
        );
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     *
     * @return array<string, mixed>
     */
    private function createProcess(array $tasks, bool $exclusive): array
    {
        return [
            'options' => [],
            'entry_point' => null,
            'end_point' => null,
            'description' => '',
            'help' => '',
            'public' => true,
            'exclusive' => $exclusive,
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
