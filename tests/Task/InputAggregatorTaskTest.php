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
use CleverAge\ProcessBundle\Task\DummyTask;
use CleverAge\ProcessBundle\Task\InputAggregatorTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(InputAggregatorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class InputAggregatorTaskTest extends TestCase
{
    private const INPUT_CODES = ['branch_a' => 'a', 'branch_b' => 'b'];

    public function testTaskIsSkippedUntilEveryInputIsReceived(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES];

        $state = $this->receive($task, $options, 'branch_a', 'A');
        self::assertTrue($state->isSkipped());
        self::assertNull($state->getOutput());

        $state = $this->receive($task, $options, 'branch_b', 'B');
        self::assertFalse($state->isSkipped());
        self::assertSame(['a' => 'A', 'b' => 'B'], $state->getOutput());
    }

    public function testBufferIsClearedAfterOutput(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES];

        $this->receive($task, $options, 'branch_a', 'A1');
        $this->receive($task, $options, 'branch_b', 'B1');

        $state = $this->receive($task, $options, 'branch_b', 'B2');
        self::assertTrue($state->isSkipped());

        $state = $this->receive($task, $options, 'branch_a', 'A2');
        self::assertSame(['b' => 'B2', 'a' => 'A2'], $state->getOutput());
    }

    public function testOverriddenInputClearsBufferByDefault(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => ['branch_a' => 'a', 'branch_b' => 'b', 'branch_c' => 'c']];

        $this->receive($task, $options, 'branch_a', 'A1');
        $this->receive($task, $options, 'branch_b', 'B1');
        // Receiving "a" again drops every buffered input, including "b"
        $this->receive($task, $options, 'branch_a', 'A2');

        $state = $this->receive($task, $options, 'branch_c', 'C1');
        self::assertTrue($state->isSkipped());

        $state = $this->receive($task, $options, 'branch_b', 'B2');
        self::assertSame(['a' => 'A2', 'c' => 'C1', 'b' => 'B2'], $state->getOutput());
    }

    public function testOverriddenInputThrowsWhenCleaningIsDisabled(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES, 'clean_input_on_override' => false];

        $this->receive($task, $options, 'branch_a', 'A1');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("The output from input 'a' has already been defined");

        $this->receive($task, $options, 'branch_a', 'A2');
    }

    public function testKeptInputsAreReusedForNextOutputs(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES, 'keep_inputs' => ['a']];

        $this->receive($task, $options, 'branch_a', 'A');
        $state = $this->receive($task, $options, 'branch_b', 'B1');
        self::assertSame(['a' => 'A', 'b' => 'B1'], $state->getOutput());

        $state = $this->receive($task, $options, 'branch_b', 'B2');
        self::assertFalse($state->isSkipped());
        self::assertSame(['a' => 'A', 'b' => 'B2'], $state->getOutput());
    }

    public function testSeveralParentsCanTargetTheSameKey(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => ['branch_a' => 'value', 'branch_b' => 'value']];

        $state = $this->receive($task, $options, 'branch_b', 'B');

        self::assertSame(['value' => 'B'], $state->getOutput());
    }

    public function testTaskWithoutPreviousStateThrows(): void
    {
        $task = new InputAggregatorTask();
        $state = $this->createState(['input_codes' => self::INPUT_CODES]);
        $task->initialize($state);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('This task cannot be used without a previous task');

        $task->execute($state);
    }

    public function testUnmappedPreviousTaskThrows(): void
    {
        $task = new InputAggregatorTask();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Task 'unknown' is not mapped in the input_codes option");

        $this->receive($task, ['input_codes' => self::INPUT_CODES], 'unknown', 'X');
    }

    public function testInputCodesOptionIsRequired(): void
    {
        $task = new InputAggregatorTask();

        $this->expectException(MissingOptionsException::class);

        $task->initialize($this->createState([]));
    }

    public function testInputsOfAnItemSkippedByTheFirstBranchAreDropped(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES];
        [$entry, $branchA, $branchB] = $this->createBranches();

        self::assertSame(['a' => 'a1', 'b' => 'b1'], $this->receiveItem($task, $options, $entry, [[$branchA, 'a1'], [$branchB, 'b1']]));
        // The item 2 is skipped by branch_a: b2 waits for a2
        self::assertNull($this->receiveItem($task, $options, $entry, [[$branchB, 'b2']]));
        // b2 is not joined with a3
        self::assertSame(['a' => 'a3', 'b' => 'b3'], $this->receiveItem($task, $options, $entry, [[$branchA, 'a3'], [$branchB, 'b3']]));
    }

    public function testInputsOfAnItemSkippedByTheLastBranchAreDropped(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES];
        [$entry, $branchA, $branchB] = $this->createBranches();

        self::assertNull($this->receiveItem($task, $options, $entry, [[$branchA, 'a1']]));
        self::assertSame(['a' => 'a2', 'b' => 'b2'], $this->receiveItem($task, $options, $entry, [[$branchA, 'a2'], [$branchB, 'b2']]));
    }

    public function testKeptInputsAreJoinedWithTheNextItems(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES, 'keep_inputs' => ['a']];
        [$entry, $branchA, $branchB] = $this->createBranches();

        self::assertSame(['a' => 'a1', 'b' => 'b1'], $this->receiveItem($task, $options, $entry, [[$branchA, 'a1'], [$branchB, 'b1']]));
        self::assertSame(['a' => 'a1', 'b' => 'b2'], $this->receiveItem($task, $options, $entry, [[$branchB, 'b2']]));
    }

    public function testInputsWithoutCommonAncestorAreJoined(): void
    {
        $task = new InputAggregatorTask();
        $options = ['input_codes' => self::INPUT_CODES];
        [, $branchA] = $this->createBranches();
        [, , $branchB] = $this->createBranches();

        $branchA->incrementExecutionCount();
        $this->receiveFrom($task, $options, $branchA, 'a1');
        $branchA->incrementExecutionCount();
        $branchB->incrementExecutionCount();

        self::assertSame(['a' => 'a1', 'b' => 'b1'], $this->receiveFrom($task, $options, $branchB, 'b1')->getOutput());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'input_codes not an array' => [['input_codes' => 'branch_a']];
        yield 'clean_input_on_override not a bool' => [['input_codes' => [], 'clean_input_on_override' => 'yes']];
        yield 'keep_inputs not an array' => [['input_codes' => [], 'keep_inputs' => 'a']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsAreRejected(array $options): void
    {
        $task = new InputAggregatorTask();

        $this->expectException(InvalidOptionsException::class);

        $task->initialize($this->createState($options));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function receive(InputAggregatorTask $task, array $options, string $previousTaskCode, mixed $input): ProcessState
    {
        $previousState = $this->createState([], $previousTaskCode);
        $state = $this->createState($options);
        $state->setPreviousState($previousState);
        $state->setInput($input);

        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    /**
     * An entry task whose outputs go to branch_a and branch_b.
     *
     * @return array{ProcessState, ProcessState, ProcessState}
     */
    private function createBranches(): array
    {
        $entry = $this->createState([], 'entry');
        $branchA = $this->createState([], 'branch_a');
        $branchA->setPreviousState($entry);
        $branchB = $this->createState([], 'branch_b');
        $branchB->setPreviousState($entry);

        return [$entry, $branchA, $branchB];
    }

    /**
     * A new item of the entry task, sent to the branches that do not skip it (in the order of the outputs).
     *
     * @param array<string, mixed>              $options
     * @param list<array{ProcessState, string}> $inputs  branch state, input
     *
     * @return array<mixed>|null the output of the task after the last input, null if it is skipped
     */
    private function receiveItem(InputAggregatorTask $task, array $options, ProcessState $entry, array $inputs): ?array
    {
        $entry->incrementExecutionCount();
        $state = null;
        foreach ($inputs as [$branch, $input]) {
            $branch->incrementExecutionCount();
            $state = $this->receiveFrom($task, $options, $branch, $input);
        }
        self::assertInstanceOf(ProcessState::class, $state);
        if ($state->isSkipped()) {
            return null;
        }
        $output = $state->getOutput();
        self::assertIsArray($output);

        return $output;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function receiveFrom(InputAggregatorTask $task, array $options, ProcessState $previousState, string $input): ProcessState
    {
        $state = $this->createState($options);
        $state->setPreviousState($previousState);
        $state->setInput($input);

        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createState(array $options, string $code = 'aggregate'): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setSkipped(false);
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration($code, 'aggregate' === $code ? InputAggregatorTask::class : DummyTask::class, $options));

        return $state;
    }
}
