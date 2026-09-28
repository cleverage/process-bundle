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
use CleverAge\ProcessBundle\Task\ColumnAggregatorTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(ColumnAggregatorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class ColumnAggregatorTaskTest extends TestCase
{
    public function testRowsAreAggregatedByColumn(): void
    {
        $output = $this->aggregate(
            ['columns' => ['a', 'b']],
            [['a' => 'x', 'b' => 1], ['a' => 'y']],
            ['ignore_missing' => true],
        );

        self::assertSame([
            'a' => ['column' => 'a', 'values' => [['a' => 'x', 'b' => 1], ['a' => 'y']]],
            'b' => ['column' => 'b', 'values' => [['a' => 'x', 'b' => 1]]],
        ], $output);
    }

    public function testNullColumnIsNotMissing(): void
    {
        $output = $this->aggregate(['columns' => ['a', 'b']], [['a' => 'x', 'b' => null]]);

        self::assertSame([
            'a' => ['column' => 'a', 'values' => [['a' => 'x', 'b' => null]]],
            'b' => ['column' => 'b', 'values' => [['a' => 'x', 'b' => null]]],
        ], $output);
    }

    public function testMissingColumnThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing columns [b] in input');

        $this->aggregate(['columns' => ['a', 'b']], [['a' => 'x']]);
    }

    /**
     * @param list<array<string, mixed>> $inputs
     */
    private function aggregate(array $options, array $inputs, array $extraOptions = []): mixed
    {
        $state = $this->createState(ColumnAggregatorTask::class, $options + $extraOptions);
        $task = new ColumnAggregatorTask(PropertyAccess::createPropertyAccessor(), new NullLogger());
        $task->initialize($state);

        foreach ($inputs as $input) {
            $state->reset(false);
            $state->setInput($input);
            $task->execute($state);
        }

        $state->reset(true);
        $task->proceed($state);

        return $state->getOutput();
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
