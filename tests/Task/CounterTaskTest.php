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

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\CounterTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(CounterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class CounterTaskTest extends TestCase
{
    public function testOutputsTheCountEveryFlushEvery(): void
    {
        [$task, $state] = $this->createTask(2);

        self::assertSame([null, 2, null, 4, null], $this->executeAll($task, $state, 5));
    }

    public function testFlushOutputsTheFinalCountOnlyOnce(): void
    {
        [$task, $state] = $this->createTask(2);
        $this->executeAll($task, $state, 5);

        self::assertSame(5, $this->flush($task, $state));
        self::assertNull($this->flush($task, $state));
        self::assertNull($this->flush($task, $state));
    }

    public function testFlushSkipsWhenTheCountWasAlreadyOutputted(): void
    {
        [$task, $state] = $this->createTask(2);
        $this->executeAll($task, $state, 4);

        self::assertNull($this->flush($task, $state));
    }

    public function testFlushSkipsWithoutExecution(): void
    {
        [$task, $state] = $this->createTask(2);

        self::assertNull($this->flush($task, $state));
    }

    public function testFlushOutputsANewCountAfterNewExecutions(): void
    {
        [$task, $state] = $this->createTask(3);
        $this->executeAll($task, $state, 2);
        self::assertSame(2, $this->flush($task, $state));

        $this->executeAll($task, $state, 2);
        self::assertSame(4, $this->flush($task, $state));
        self::assertNull($this->flush($task, $state));
    }

    /**
     * A modulo by zero failed when executed.
     */
    public function testZeroFlushEveryIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "flush_every" of the counter task must be a positive integer (the count is output every "flush_every" executions), 0 given.');

        $this->createTask(0);
    }

    /**
     * A negative value works as its absolute value (sign of the modulo): deprecated.
     */
    public function testNegativeFlushEveryIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            [$task, $state] = $this->createTask(-2);
        } finally {
            restore_error_handler();
        }

        self::assertSame(['Since cleverage/process-bundle 5.10: A negative "flush_every" option of the counter task (-2) is deprecated, it works as 2: use a positive integer. It will be rejected in v6.0.'], $deprecations);
        self::assertSame([null, 2, null, 4, null], $this->executeAll($task, $state, 5));
        self::assertSame(5, $this->flush($task, $state));
    }

    /**
     * @return array{CounterTask, ProcessState}
     */
    private function createTask(int $flushEvery): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('counter', CounterTask::class, ['flush_every' => $flushEvery]));
        $task = new CounterTask();
        $task->initialize($state);

        return [$task, $state];
    }

    /**
     * Execute the task $count times, collecting outputs (null when skipped).
     *
     * @return list<mixed>
     */
    private function executeAll(CounterTask $task, ProcessState $state, int $count): array
    {
        $outputs = [];
        for ($i = 0; $i < $count; ++$i) {
            $state->reset(false);
            $state->setInput($i);
            $task->execute($state);
            $outputs[] = $state->isSkipped() ? null : $state->getOutput();
        }

        return $outputs;
    }

    private function flush(CounterTask $task, ProcessState $state): mixed
    {
        $state->reset(true);
        $task->flush($state);

        return $state->isSkipped() ? null : $state->getOutput();
    }
}
