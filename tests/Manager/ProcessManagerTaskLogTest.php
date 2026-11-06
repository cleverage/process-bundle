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
use CleverAge\ProcessBundle\Task\DummyTask;
use CleverAge\ProcessBundle\Task\SimpleBatchTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The debug log of the execution of a task is written once by execution of its process, not for each item.
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
#[\PHPUnit\Framework\Attributes\UsesClass(DummyTask::class)]
class ProcessManagerTaskLogTest extends TestCase
{
    private ProcessManager $processManager;

    /**
     * @var list<string>
     */
    private array $messages = [];

    protected function setUp(): void
    {
        $container = new Container();
        $container->set('test.iterate', new ConstantIterableOutputTask());
        $container->set('test.child_iterate', new ConstantIterableOutputTask());
        $container->set('test.dummy', new DummyTask());
        $container->set('test.batch', new SimpleBatchTask());
        $container->set('test.aggregate', new AggregateIterableTask());
        // Runs the given sub-process and forwards its input, as the process_executor task
        $container->set('test.sub_process', new class($this) implements TaskInterface {
            public function __construct(
                private readonly ProcessManagerTaskLogTest $test,
            ) {
            }

            public function execute(ProcessState $state): void
            {
                $this->test->executeSubProcess((string) $state->getTaskConfiguration()->getOption('process'));
                $state->setOutput($state->getInput());
            }
        });

        $registry = new ProcessConfigurationRegistry(
            [
                'test.parent' => $this->createProcessConfiguration([
                    'iterate' => $this->createTaskConfiguration('@test.iterate', ['output' => [1, 2, 3]], ['sub_process']),
                    'sub_process' => $this->createTaskConfiguration('@test.sub_process', ['process' => 'test.child'], ['batch']),
                    'batch' => $this->createTaskConfiguration('@test.batch', [], ['aggregate']),
                    'aggregate' => $this->createTaskConfiguration('@test.aggregate'),
                ]),
                'test.child' => $this->createProcessConfiguration([
                    'child_iterate' => $this->createTaskConfiguration('@test.child_iterate', ['output' => ['a', 'b']], ['child_dummy']),
                    'child_dummy' => $this->createTaskConfiguration('@test.dummy'),
                ]),
            ],
            'stop'
        );

        $logger = new class(function (string $message): void {
            $this->messages[] = $message;
        }) extends PsrAbstractLogger {
            public function __construct(
                private readonly \Closure $collector,
            ) {
            }

            /**
             * @param array<string, mixed> $context
             */
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ($this->collector)((string) $message);
            }
        };

        $this->processManager = new ProcessManager(
            $container,
            new ProcessLogger($logger),
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

    public function testATaskIsLoggedOnceByExecutionOfItsProcess(): void
    {
        $this->processManager->execute('test.parent');

        self::assertSame([
            'Processing task iterate' => 1,
            'Processing task sub_process' => 1,
            // Once by execution of the sub-process, for its 2 items
            'Processing task child_iterate' => 3,
            'Processing task child_dummy' => 3,
            'Processing task batch' => 1,
            'Processing task aggregate' => 1,
            'Proceeding task aggregate' => 1,
        ], $this->countTaskMessages(['Processing', 'Proceeding']));
    }

    public function testATaskIsLoggedAgainByTheNextExecutionOfItsProcess(): void
    {
        $this->processManager->execute('test.parent');
        $this->processManager->execute('test.parent');

        self::assertSame(2, $this->countTaskMessages(['Processing'])['Processing task batch'] ?? 0);

        $this->processManager->reset();
        $this->processManager->execute('test.parent');

        self::assertSame(3, $this->countTaskMessages(['Processing'])['Processing task batch'] ?? 0);
    }

    /**
     * @param list<string> $prefixes
     *
     * @return array<string, int>
     */
    private function countTaskMessages(array $prefixes): array
    {
        $counts = [];
        foreach ($this->messages as $message) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($message, "{$prefix} task ")) {
                    $counts[$message] = ($counts[$message] ?? 0) + 1;
                }
            }
        }

        return $counts;
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
