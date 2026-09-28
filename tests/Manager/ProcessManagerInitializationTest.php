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
use CleverAge\ProcessBundle\Model\InitializableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * An exception thrown by initialize() does not abort the process (deprecated since v5, the process will fail before
 * executing any task in v6.0).
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessFailedException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractLogger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfigurationRegistry::class)]
class ProcessManagerInitializationTest extends TestCase
{
    /** @var list<string> */
    private array $executedTasks = [];

    /** @var list<string> */
    private array $deprecations = [];

    public function testInitializationFailureIsDeprecatedAndUpstreamTasksStillRun(): void
    {
        $processManager = $this->createProcessManager(skipBeforeBadTask: false);

        $exception = null;
        try {
            $this->execute($processManager);
        } catch (\Throwable $exception) {
        }

        self::assertInstanceOf(ProcessFailedException::class, $exception, 'The process should fail when reaching the badly initialized task');
        self::assertStringContainsString('Invalid configuration', $exception->getMessage());
        self::assertSame(['entry', 'bad'], $this->executedTasks);
        self::assertSame([$this->getExpectedDeprecation()], $this->deprecations);
    }

    public function testInitializationFailureOfAnUnreachedTaskIsDeprecated(): void
    {
        $processManager = $this->createProcessManager(skipBeforeBadTask: true);

        $this->execute($processManager);

        self::assertSame(['entry'], $this->executedTasks);
        self::assertSame([$this->getExpectedDeprecation()], $this->deprecations);
    }

    public function testNoDeprecationWithoutInitializationFailure(): void
    {
        $processManager = $this->createProcessManager(skipBeforeBadTask: false, failInitialization: false);

        $this->execute($processManager);

        self::assertSame(['entry', 'bad'], $this->executedTasks);
        self::assertSame([], $this->deprecations);
    }

    private function getExpectedDeprecation(): string
    {
        return 'The initialization of the task "bad" of the process "test.process" has failed with message "Invalid configuration". Going on with the process after an initialization failure is deprecated since v5: in v6.0, the process will fail before executing any task.';
    }

    private function execute(ProcessManager $processManager): void
    {
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $processManager->execute('test.process');
        } finally {
            restore_error_handler();
        }
    }

    private function createProcessManager(bool $skipBeforeBadTask, bool $failInitialization = true): ProcessManager
    {
        $recorder = function (string $taskCode): void {
            $this->executedTasks[] = $taskCode;
        };

        $entryTask = new class($recorder, $skipBeforeBadTask) implements TaskInterface {
            public function __construct(
                private readonly \Closure $recorder,
                private readonly bool $skip,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                ($this->recorder)('entry');
                $state->setOutput('value');
                $state->setSkipped($this->skip);
            }
        };

        $badTask = new class($recorder, $failInitialization) implements InitializableTaskInterface {
            public function __construct(
                private readonly \Closure $recorder,
                private readonly bool $fail,
            ) {
            }

            public function initialize(ProcessState $state): void
            {
                if ($this->fail) {
                    throw new \InvalidArgumentException('Invalid configuration');
                }
            }

            /**
             * Like configurable tasks (options are resolved again), fail again when executed.
             */
            public function execute(ProcessState $state): void
            {
                ($this->recorder)('bad');
                $this->initialize($state);
            }
        };

        $container = new Container();
        $container->set('test.entry', $entryTask);
        $container->set('test.bad', $badTask);

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
                        'entry' => $this->createTaskConfiguration('@test.entry', ['bad']),
                        'bad' => $this->createTaskConfiguration('@test.bad'),
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
        );
    }

    /**
     * @param list<string> $outputs
     *
     * @return array<string, mixed>
     */
    private function createTaskConfiguration(string $service, array $outputs = []): array
    {
        return [
            'service' => $service,
            'options' => [],
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
