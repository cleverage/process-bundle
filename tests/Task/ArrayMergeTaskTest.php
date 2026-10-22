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
use CleverAge\ProcessBundle\Task\ArrayMergeTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayMergeTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class ArrayMergeTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, list<array<mixed>>, array<mixed>}>
     */
    public static function mergeProvider(): iterable
    {
        $inputs = [
            ['a' => 1, 'b' => ['c' => 2], 0 => 'x'],
            ['b' => ['d' => 3], 0 => 'y'],
        ];

        yield 'default is array_merge' => [[], $inputs, ['a' => 1, 'b' => ['d' => 3], 0 => 'x', 1 => 'y']];
        yield 'array_merge' => [
            ['merge_function' => 'array_merge'],
            $inputs,
            ['a' => 1, 'b' => ['d' => 3], 0 => 'x', 1 => 'y'],
        ];
        yield 'array_merge_recursive' => [
            ['merge_function' => 'array_merge_recursive'],
            $inputs,
            ['a' => 1, 'b' => ['c' => 2, 'd' => 3], 0 => 'x', 1 => 'y'],
        ];
        yield 'array_replace' => [
            ['merge_function' => 'array_replace'],
            $inputs,
            ['a' => 1, 'b' => ['d' => 3], 0 => 'y'],
        ];
        yield 'array_replace_recursive' => [
            ['merge_function' => 'array_replace_recursive'],
            $inputs,
            ['a' => 1, 'b' => ['c' => 2, 'd' => 3], 0 => 'y'],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param list<array<mixed>>   $inputs
     * @param array<mixed>         $expected
     */
    #[DataProvider('mergeProvider')]
    public function testInputsAreMergedWithMergeFunction(array $options, array $inputs, array $expected): void
    {
        $task = new ArrayMergeTask();
        $state = $this->createState($options);
        $task->initialize($state);

        foreach ($inputs as $input) {
            $state->setInput($input);
            $task->execute($state);
            self::assertNull($state->getOutput(), 'Blocking task must not output before proceed');
        }
        $task->proceed($state);

        self::assertSame($expected, $state->getOutput());
    }

    public function testProceedWithoutInputOutputsEmptyArray(): void
    {
        $task = new ArrayMergeTask();
        $state = $this->createState([]);
        $task->initialize($state);

        $task->proceed($state);

        self::assertSame([], $state->getOutput());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonArrayInputProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'int' => [1];
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('nonArrayInputProvider')]
    public function testNonArrayInputThrowsException(mixed $input): void
    {
        $task = new ArrayMergeTask();
        $state = $this->createState([]);
        $task->initialize($state);
        $state->setInput($input);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Input must be an array');
        $task->execute($state);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidMergeFunctionProvider(): iterable
    {
        yield 'unknown function' => ['array_combine'];
        yield 'non string' => [['array_merge']];
    }

    #[DataProvider('invalidMergeFunctionProvider')]
    public function testInvalidMergeFunctionIsRejected(mixed $mergeFunction): void
    {
        $task = new ArrayMergeTask();
        $state = $this->createState(['merge_function' => $mergeFunction]);

        $this->expectException(InvalidOptionsException::class);
        $task->initialize($state);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('merge', ArrayMergeTask::class, $options));

        return $state;
    }
}
