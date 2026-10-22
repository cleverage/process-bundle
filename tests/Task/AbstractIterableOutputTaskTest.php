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
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use CleverAge\ProcessBundle\Task\ConstantIterableOutputTask;
use CleverAge\ProcessBundle\Task\InputIteratorTask;
use CleverAge\ProcessBundle\Task\SplitJoinLineTask;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ConstantIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(InputIteratorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SplitJoinLineTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class AbstractIterableOutputTaskTest extends TestCase
{
    public function testEmptyConstantIterableIsSkipped(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => []]);
        $task->initialize($state);

        $task->execute($state);

        self::assertTrue($state->isSkipped());
        self::assertNull($state->getException());
        self::assertSame([], $state->getErrorContext());
        self::assertFalse($task->next($state));
    }

    public function testEmptyInputIteratorIsSkipped(): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState([], []);
        $task->initialize($state);

        $task->execute($state);

        self::assertTrue($state->isSkipped());
        self::assertSame([], $state->getErrorContext());
        self::assertFalse($task->next($state));
    }

    public function testIterationSetsIteratorKeyInErrorContext(): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState([], ['a' => 'foo', 'b' => 'bar']);
        $task->initialize($state);

        $outputs = [];
        $keys = [];
        do {
            $state->setSkipped(false);
            $task->execute($state);
            $outputs[] = $state->getOutput();
            $keys[] = $state->getErrorContext()['iterator_key'] ?? null;
        } while ($task->next($state));

        self::assertSame(['foo', 'bar'], $outputs);
        self::assertSame(['a', 'b'], $keys);
        self::assertSame([], $state->getErrorContext());

        // A new input starts a new iteration cycle; an empty one is skipped
        $state->setInput([]);
        $task->execute($state);
        self::assertTrue($state->isSkipped());
        self::assertSame([], $state->getErrorContext());
    }

    public function testSplitJoinLineWithoutSplitColumnIsSkipped(): void
    {
        $task = new SplitJoinLineTask();
        $state = $this->createState(['split_columns' => [], 'join_column' => 'value'], ['name' => 'Item1']);
        $task->initialize($state);

        $task->execute($state);

        self::assertTrue($state->isSkipped());
        self::assertSame([], $state->getErrorContext());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createState(array $options, mixed $input = null): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('iterate', AbstractIterableOutputTask::class, $options));
        $state->setInput($input);

        return $state;
    }
}
