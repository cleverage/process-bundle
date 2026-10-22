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
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The process history returned by ProcessManager::getProcessHistory() (used by the log processors) after a failure.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessFailedException::class)]
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
class ProcessManagerProcessHistoryTest extends TestCase
{
    private Container $container;

    private ProcessManager $processManager;

    /**
     * What the tasks saw of the process manager history while the process was running.
     */
    private mixed $observedHistory = null;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->set('test.constant', new ConstantOutputTask());
        $this->container->set('test.failing', new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
                throw new \LogicException('Something went wrong');
            }
        });
        // Runs a failing sub-process and goes on, as the process_executor task with the skip error strategy
        $this->container->set('test.sub_process', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerProcessHistoryTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->executeSubProcess();
            }
        });

        $registry = new ProcessConfigurationRegistry(
            [
                'test.parent' => $this->createProcessConfiguration([
                    'entry' => $this->createTaskConfiguration('@test.constant', ['output' => 'value'], ['sub_process']),
                    'sub_process' => $this->createTaskConfiguration('@test.sub_process'),
                ]),
                'test.reset' => $this->createProcessConfiguration([
                    'reset' => $this->createTaskConfiguration('@test.reset'),
                ]),
                'test.failing' => $this->createProcessConfiguration([
                    'entry' => $this->createTaskConfiguration('@test.constant', ['output' => 'value'], ['fail']),
                    'fail' => $this->createTaskConfiguration('@test.failing'),
                ]),
            ],
            'stop'
        );

        $this->processManager = new ProcessManager(
            $this->container,
            new ProcessLogger(new NullLogger()),
            new TaskLogger(new NullLogger()),
            $registry,
            new ContextualOptionResolver(),
            new EventDispatcher(),
            taskRegistry: new TaskRegistry($this->container),
        );
    }

    public function executeSubProcess(): void
    {
        try {
            $this->processManager->execute('test.failing');
        } catch (ProcessFailedException) {
        }
        $this->observedHistory = $this->processManager->getProcessHistory()?->getProcessCode();
    }

    public function testTheParentHistoryIsRestoredAfterAFailedSubProcess(): void
    {
        $this->processManager->execute('test.parent');

        self::assertSame('test.parent', $this->observedHistory);
        self::assertNull($this->processManager->getProcessHistory());
    }

    public function testNoHistoryIsLeftAfterAFailedProcess(): void
    {
        try {
            $this->processManager->execute('test.failing');
            self::fail('The process should have failed');
        } catch (ProcessFailedException) {
        }

        self::assertNull($this->processManager->getProcessHistory());
    }

    public function testResetForgetsTheCurrentExecution(): void
    {
        $this->container->set('test.reset', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerProcessHistoryTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->resetProcessManager();
            }
        });

        $this->processManager->execute('test.reset');

        self::assertSame(['test.reset', null], $this->observedHistory);
    }

    public function resetProcessManager(): void
    {
        $before = $this->processManager->getProcessHistory()?->getProcessCode();
        $this->processManager->reset();
        $this->observedHistory = [$before, $this->processManager->getProcessHistory()];
        self::assertNull($this->processManager->getTaskConfiguration());
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
