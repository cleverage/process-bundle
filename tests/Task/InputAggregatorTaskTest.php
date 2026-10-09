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
