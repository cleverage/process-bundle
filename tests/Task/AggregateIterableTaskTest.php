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
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\AggregateIterableTask;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(AggregateIterableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class AggregateIterableTaskTest extends TestCase
{
    public function testInputsAreAggregatedInReceptionOrder(): void
    {
        $task = new AggregateIterableTask();
        $state = $this->createState();
        $object = new \stdClass();

        foreach ([1, 'two', ['three'], null, $object] as $input) {
            $state->setInput($input);
            $task->execute($state);
            self::assertNull($state->getOutput());
            self::assertFalse($state->isSkipped());
        }

        $task->proceed($state);

        self::assertFalse($state->isSkipped());
        self::assertSame([1, 'two', ['three'], null, $object], $state->getOutput());
    }

    public function testIdenticalInputsAreAllKept(): void
    {
        $task = new AggregateIterableTask();
        $state = $this->createState();

        foreach (['a', 'a', 'a'] as $input) {
            $state->setInput($input);
            $task->execute($state);
        }
        $task->proceed($state);

        self::assertSame(['a', 'a', 'a'], $state->getOutput());
    }

    public function testProceedWithoutInputIsSkipped(): void
    {
        $task = new AggregateIterableTask();
        $state = $this->createState();

        $task->proceed($state);

        self::assertTrue($state->isSkipped());
        self::assertNull($state->getOutput());
    }

    private function createState(): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setSkipped(false);
        $state->setTaskConfiguration(new TaskConfiguration('aggregate', AggregateIterableTask::class, []));

        return $state;
    }
}
