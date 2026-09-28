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
use CleverAge\ProcessBundle\Task\IterableBatchTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[\PHPUnit\Framework\Attributes\CoversClass(IterableBatchTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class IterableBatchTaskTest extends TestCase
{
    public function testBatchCountIteratesOverTheBuffer(): void
    {
        [$task, $state] = $this->createTask(['batch_count' => 2]);

        self::assertSame([null], $this->execute($task, $state, 'a'));
        self::assertSame(['a', 'b'], $this->execute($task, $state, 'b'));
        self::assertSame([null], $this->execute($task, $state, 'c'));
        self::assertSame(['c'], $this->flush($task, $state));
    }

    public function testNullBatchCountOnlyOutputsOnFlush(): void
    {
        [$task, $state] = $this->createTask(['batch_count' => null]);

        self::assertSame([null], $this->execute($task, $state, 'a'));
        self::assertSame([null], $this->execute($task, $state, 'b'));
        self::assertSame([null], $this->execute($task, $state, 'c'));
        self::assertSame(['a', 'b', 'c'], $this->flush($task, $state));
    }

    /**
     * @return array{IterableBatchTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $state = $this->createState(IterableBatchTask::class, $options);
        $task = new IterableBatchTask(new NullLogger());
        $task->initialize($state);

        return [$task, $state];
    }

    /**
     * Execute the task like the ProcessManager does, collecting outputs (null when skipped).
     *
     * @return list<mixed>
     */
    private function execute(IterableBatchTask $task, ProcessState $state, mixed $input): array
    {
        $outputs = [];
        do {
            $state->reset(false);
            $state->setInput($input);
            $task->execute($state);
            $outputs[] = $state->isSkipped() ? null : $state->getOutput();
        } while ($task->next($state));

        return $outputs;
    }

    /**
     * @return list<mixed>
     */
    private function flush(IterableBatchTask $task, ProcessState $state): array
    {
        $outputs = [];
        do {
            $state->reset(true);
            $task->flush($state);
            if (!$state->isSkipped()) {
                $outputs[] = $state->getOutput();
            }
        } while ($task->next($state));

        return $outputs;
    }

    private function createState(string $class, array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('task', $class, $options));

        return $state;
    }
}
