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
use CleverAge\ProcessBundle\Task\SplitJoinLineTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(SplitJoinLineTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class SplitJoinLineTaskTest extends TestCase
{
    public function testColumnsAreSplitIntoJoinColumn(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => ['category', 'tag'], 'join_column' => 'value']);

        $outputs = $this->iterate($task, $state, ['category' => 'A,B,C', 'tag' => 'x,y', 'name' => 'Item1']);

        self::assertSame([
            ['name' => 'Item1', 'value' => 'A'],
            ['name' => 'Item1', 'value' => 'B'],
            ['name' => 'Item1', 'value' => 'C'],
            ['name' => 'Item1', 'value' => 'x'],
            ['name' => 'Item1', 'value' => 'y'],
        ], $outputs);
    }

    public function testCustomSplitCharacter(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => ['assets'], 'join_column' => 'asset', 'split_character' => '|']);

        $outputs = $this->iterate($task, $state, ['product' => 'toto', 'assets' => 'a|b,c']);

        self::assertSame([
            ['product' => 'toto', 'asset' => 'a'],
            ['product' => 'toto', 'asset' => 'b,c'],
        ], $outputs);
    }

    public function testJoinColumnCanOverrideAnExistingColumn(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => ['codes'], 'join_column' => 'name']);

        $outputs = $this->iterate($task, $state, ['name' => 'Item1', 'codes' => '1,2']);

        self::assertSame([['name' => '1'], ['name' => '2']], $outputs);
    }

    public function testNonStringValuesAreCastToString(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => ['id', 'empty'], 'join_column' => 'value']);

        $outputs = $this->iterate($task, $state, ['id' => 42, 'empty' => null]);

        self::assertSame([['value' => '42'], ['value' => '']], $outputs);
    }

    public function testIterationRestartsForEachNewInput(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => ['assets'], 'join_column' => 'asset']);

        self::assertSame(
            [['product' => 'toto', 'asset' => 'a'], ['product' => 'toto', 'asset' => 'b']],
            $this->iterate($task, $state, ['product' => 'toto', 'assets' => 'a,b'])
        );
        self::assertSame(
            [['product' => 'tata', 'asset' => 'e'], ['product' => 'tata', 'asset' => 'f']],
            $this->iterate($task, $state, ['product' => 'tata', 'assets' => 'e,f'])
        );
    }

    public function testEmptySplitColumnsIsSkipped(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => [], 'join_column' => 'value']);

        self::assertSame([], $this->iterate($task, $state, ['name' => 'Item1']));
    }

    public function testMissingSplitColumnThrows(): void
    {
        [$task, $state] = $this->createTask(['split_columns' => ['category', 'tag'], 'join_column' => 'value']);
        $state->setInput(['category' => 'A']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing column tag');

        $task->execute($state);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'missing split_columns' => [['join_column' => 'value'], MissingOptionsException::class];
        yield 'missing join_column' => [['split_columns' => ['a']], MissingOptionsException::class];
        yield 'split_columns not an array' => [['split_columns' => 'a', 'join_column' => 'value'], InvalidOptionsException::class];
        yield 'join_column not a string' => [['split_columns' => ['a'], 'join_column' => 1], InvalidOptionsException::class];
    }

    /**
     * @param array<string, mixed>     $options
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsAreRejected(array $options, string $exception): void
    {
        $this->expectException($exception);

        $this->createTask($options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{SplitJoinLineTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('split', SplitJoinLineTask::class, $options));

        $task = new SplitJoinLineTask();
        $task->initialize($state);

        return [$task, $state];
    }

    /**
     * @return list<mixed>
     */
    private function iterate(SplitJoinLineTask $task, ProcessState $state, mixed $input): array
    {
        $outputs = [];
        $state->setInput($input);
        do {
            $state->setSkipped(false);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $outputs[] = $state->getOutput();
            }
        } while ($task->next($state));

        return $outputs;
    }
}
