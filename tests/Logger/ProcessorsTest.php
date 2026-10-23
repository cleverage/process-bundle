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

namespace CleverAge\ProcessBundle\Tests\Logger;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Logger\AbstractProcessor;
use CleverAge\ProcessBundle\Logger\ProcessProcessor;
use CleverAge\ProcessBundle\Logger\TaskProcessor;
use CleverAge\ProcessBundle\Logger\TransformerProcessor;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The Monolog processors add the current process and task to the log records.
 */
#[CoversClass(AbstractProcessor::class)]
#[CoversClass(ProcessProcessor::class)]
#[CoversClass(TaskProcessor::class)]
#[CoversClass(TransformerProcessor::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ProcessHistory::class)]
#[UsesClass(ProcessState::class)]
class ProcessorsTest extends TestCase
{
    public function testNothingIsAddedOutsideOfAProcess(): void
    {
        $processManager = $this->createStub(ProcessManager::class);

        foreach ([ProcessProcessor::class, TaskProcessor::class, TransformerProcessor::class] as $class) {
            $record = (new $class($processManager))($this->createRecord(['foo' => 'bar']));

            self::assertSame([], $record->extra, $class);
            self::assertSame(['foo' => 'bar'], $record->context, $class);
        }
    }

    public function testTheProcessProcessorAddsTheProcess(): void
    {
        [$processManager, $history] = $this->createProcessManager();

        $record = (new ProcessProcessor($processManager))($this->createRecord());

        self::assertSame(
            ['process_id' => $history->getId(), 'process_code' => 'test.process', 'process_context' => ['key' => 'value']],
            $record->extra
        );
    }

    public function testTheTaskAndTransformerProcessorsAddTheTaskAndItsError(): void
    {
        [$processManager, $history, $state] = $this->createProcessManager();
        $exception = new \RuntimeException('Something went wrong');
        $state->setErrorOutput(['invalid' => 'item']);
        $state->setException($exception);

        foreach ([TaskProcessor::class, TransformerProcessor::class] as $class) {
            $record = (new $class($processManager))($this->createRecord());

            self::assertSame(
                [
                    'process_id' => $history->getId(),
                    'process_code' => 'test.process',
                    'process_context' => ['key' => 'value'],
                    'task_code' => 'my_task',
                    'task_service' => '@app.task',
                    'error' => ['invalid' => 'item'],
                    'exception' => $exception,
                ],
                $record->extra,
                $class
            );
        }
    }

    /**
     * @return array{ProcessManager, ProcessHistory, ProcessState}
     */
    private function createProcessManager(): array
    {
        $processConfiguration = new ProcessConfiguration('test.process', []);
        $history = new ProcessHistory($processConfiguration, ['key' => 'value']);
        $taskConfiguration = new TaskConfiguration('my_task', '@app.task', []);
        $state = new ProcessState($processConfiguration, $history);
        $taskConfiguration->setState($state);

        $processManager = $this->createStub(ProcessManager::class);
        $processManager->method('getProcessHistory')->willReturn($history);
        $processManager->method('getTaskConfiguration')->willReturn($taskConfiguration);

        return [$processManager, $history, $state];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function createRecord(array $context = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'cleverage_process', Level::Info, 'message', $context);
    }
}
