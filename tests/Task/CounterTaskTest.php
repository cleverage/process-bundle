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
