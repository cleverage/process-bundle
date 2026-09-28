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
use CleverAge\ProcessBundle\Task\ConstantOutputTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessFailedException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
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

    private function createProcessManager(\Throwable $exception, string $errorStrategy = 'stop'): ProcessManager
    {
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
                        'fail' => $this->createTaskConfiguration('@test.failing', [], [], $errorStrategy),
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
     * @param array<string, mixed> $options
     * @param list<string>         $outputs
     *
     * @return array<string, mixed>
     */
    private function createTaskConfiguration(
        string $service,
        array $options = [],
        array $outputs = [],
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
