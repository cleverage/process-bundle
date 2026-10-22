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
use CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(GroupByAggregateIterableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class GroupByAggregateIterableTaskTest extends TestCase
{
    public function testInputsAreGroupedAndLastInputWins(): void
    {
        [$task, $state] = $this->createTask(['[type]', '[code]']);

        $outputs = $this->aggregate($task, $state, [
            ['type' => 'A', 'code' => 1, 'v' => 'x'],
            ['type' => 'B', 'code' => 1, 'v' => 'z'],
            ['type' => 'A', 'code' => 1, 'v' => 'y'],
        ]);

        self::assertNull($state->getException());
        self::assertSame([
            'A-1' => ['type' => 'A', 'code' => 1, 'v' => 'y'],
            'B-1' => ['type' => 'B', 'code' => 1, 'v' => 'z'],
        ], $outputs);
    }

    public function testObjectInputsAreReadWithPropertyAccessor(): void
    {
        [$task, $state] = $this->createTask(['type']);
        $first = (object) ['type' => 'A'];
        $second = (object) ['type' => 'B'];

        $outputs = $this->aggregate($task, $state, [$first, $second]);

        self::assertSame(['A' => $first, 'B' => $second], $outputs);
    }

    public function testEmptyAccessorsGroupEverythingUnderAnEmptyKey(): void
    {
        [$task, $state] = $this->createTask([]);

        $outputs = $this->aggregate($task, $state, [['v' => 1], ['v' => 2]]);

        self::assertSame(['' => ['v' => 2]], $outputs);
    }

    public function testUnreadablePropertySetsExceptionWithErrorContext(): void
    {
        [$task, $state] = $this->createTask(['type', 'missing']);
        $state->setInput((object) ['type' => 'A']);

        $task->execute($state);

        self::assertInstanceOf(NoSuchPropertyException::class, $state->getException());
        self::assertSame(['property' => 'missing'], $state->getErrorContext());

        // The failed input is not aggregated
        $task->proceed($state);
        self::assertTrue($state->isSkipped());
    }

    public function testProceedWithoutInputIsSkipped(): void
    {
        [$task, $state] = $this->createTask(['[type]']);

        $task->proceed($state);

        self::assertTrue($state->isSkipped());
        self::assertNull($state->getOutput());
    }

    public function testGroupByOptionIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->createTask(null);
    }

    public function testGroupByOptionMustBeAnArray(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->createTask('[type]');
    }

    /**
     * @return array{GroupByAggregateIterableTask, ProcessState}
     */
    private function createTask(mixed $groupBy): array
    {
        $options = null === $groupBy ? [] : [GroupByAggregateIterableTask::GROUP_BY_OPTION => $groupBy];
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setSkipped(false);
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('group', GroupByAggregateIterableTask::class, $options));

        $task = new GroupByAggregateIterableTask(PropertyAccess::createPropertyAccessor());
        $task->initialize($state);

        return [$task, $state];
    }

    /**
     * @param list<mixed> $inputs
     */
    private function aggregate(GroupByAggregateIterableTask $task, ProcessState $state, array $inputs): mixed
    {
        foreach ($inputs as $input) {
            $state->setInput($input);
            $task->execute($state);
        }
        $task->proceed($state);
        self::assertFalse($state->isSkipped());

        return $state->getOutput();
    }
}
