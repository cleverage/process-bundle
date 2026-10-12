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
use CleverAge\ProcessBundle\Task\StopTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessManager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessConfigurationRegistry::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessFailedException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractLogger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ConstantOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(StopTask::class)]
class ProcessManagerEndLogTest extends TestCase
{
    /**
     * @var list<array{string, string, array<string, mixed>}>
     */
    private array $records = [];

    public function testSucceededProcessIsLoggedAsInfoByDefault(): void
    {
        $this->createProcessManager('@test.constant')->execute('test.process');

        self::assertSame([[LogLevel::INFO, 'Process test.process succeed']], $this->getEndRecords());
    }

    public function testSucceededProcessUsesTheDefaultSuccessLevel(): void
    {
        $this->createProcessManager('@test.constant', defaultLogLevels: ['success_level' => LogLevel::DEBUG])
            ->execute('test.process');

        self::assertSame([[LogLevel::DEBUG, 'Process test.process succeed']], $this->getEndRecords());
    }

    public function testSucceededProcessUsesTheLevelOfTheProcess(): void
    {
        $this->createProcessManager(
            '@test.constant',
            processLogLevels: ['success_level' => LogLevel::NOTICE, 'failed_level' => null],
            defaultLogLevels: ['success_level' => LogLevel::DEBUG]
        )->execute('test.process');

        self::assertSame([[LogLevel::NOTICE, 'Process test.process succeed']], $this->getEndRecords());
    }

    public function testSkippedErrorDoesNotFailTheProcess(): void
    {
        $this->createProcessManager('@test.failing', 'skip')->execute('test.process');

        self::assertSame([[LogLevel::INFO, 'Process test.process succeed']], $this->getEndRecords());
    }

    public function testStoppedProcessIsLoggedAsFailedAsDebugByDefault(): void
    {
        $this->createProcessManager('@test.stop')->execute('test.process');

        self::assertSame([[LogLevel::DEBUG, 'Process test.process failed']], $this->getEndRecords());
    }

    public function testStoppedProcessUsesTheDefaultFailedLevel(): void
    {
        $this->createProcessManager('@test.stop', defaultLogLevels: ['failed_level' => LogLevel::ERROR])
            ->execute('test.process');

        self::assertSame([[LogLevel::ERROR, 'Process test.process failed']], $this->getEndRecords());
    }

    public function testFailingProcessIsLoggedAsFailedWithTheLevelOfTheProcess(): void
    {
        $processManager = $this->createProcessManager(
            '@test.failing',
            processLogLevels: ['success_level' => null, 'failed_level' => LogLevel::WARNING],
            defaultLogLevels: ['failed_level' => LogLevel::ERROR]
        );

        try {
            $processManager->execute('test.process');
            self::fail('The process should have failed');
        } catch (ProcessFailedException) {
        }

        self::assertSame([[LogLevel::WARNING, 'Process test.process failed']], $this->getEndRecords());
    }

    /**
     * @param array{success_level: ?string, failed_level: ?string}|null $processLogLevels
     * @param array{success_level?: string, failed_level?: string}      $defaultLogLevels
     */
    private function createProcessManager(
        string $service,
        string $errorStrategy = 'stop',
        ?array $processLogLevels = null,
        array $defaultLogLevels = [],
    ): ProcessManager {
        $failingTask = new class implements TaskInterface {
            public function execute(ProcessState $state): void
            {
                throw new \LogicException('Something went wrong');
            }
        };

        $container = new Container();
        $container->set('test.constant', new ConstantOutputTask());
        $container->set('test.failing', $failingTask);
        $container->set('test.stop', new StopTask());

        $rawConfiguration = [
            'options' => [],
            'entry_point' => null,
            'end_point' => null,
            'description' => '',
            'help' => '',
            'public' => true,
            'tasks' => [
                'entry' => $this->createTaskConfiguration('@test.constant', ['output' => 'value'], ['last']),
                'last' => $this->createTaskConfiguration($service, errorStrategy: $errorStrategy),
            ],
        ];
        if (null !== $processLogLevels) {
            $rawConfiguration['logs'] = $processLogLevels;
        }

        $logger = new class(function (string $level, string $message, array $context): void {
            $this->records[] = [$level, $message, $context];
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
                ($this->collector)((string) $level, (string) $message, $context);
            }
        };

        return new ProcessManager(
            $container,
            new ProcessLogger($logger),
            new TaskLogger(new NullLogger()),
            new ProcessConfigurationRegistry(['test.process' => $rawConfiguration], 'stop', $defaultLogLevels),
            new ContextualOptionResolver(),
            new EventDispatcher(),
        );
    }

    /**
     * Level and message of the end of process logs, which must have an integer duration.
     *
     * @return list<array{string, string}>
     */
    private function getEndRecords(): array
    {
        $endRecords = [];
        foreach ($this->records as [$level, $message, $context]) {
            if (str_ends_with($message, ' succeed') || str_ends_with($message, ' failed')) {
                self::assertIsInt($context['duration'] ?? null);
                $endRecords[] = [$level, $message];
            }
        }

        return $endRecords;
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
