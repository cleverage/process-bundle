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
use CleverAge\ProcessBundle\Task\InputIteratorTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(InputIteratorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class InputIteratorTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function iterableInputProvider(): iterable
    {
        yield 'list' => [[1, 2, 3]];
        yield 'associative array' => [['a' => 1, 'b' => 2, 'c' => 3]];
        yield 'iterator' => [new \ArrayIterator(['a' => 1, 'b' => 2, 'c' => 3])];
        yield 'iterator aggregate' => [new \ArrayObject([1, 2, 3])];
    }

    #[DataProvider('iterableInputProvider')]
    public function testEachValueIsOutput(mixed $input): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState();
        $task->initialize($state);

        self::assertSame([1, 2, 3], $this->iterate($task, $state, $input));
    }

    public function testGeneratorInputIsIterated(): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState();
        $task->initialize($state);

        $generator = (static function (): \Generator {
            yield 'x';
            yield 'y';
        })();

        self::assertSame(['x', 'y'], $this->iterate($task, $state, $generator));
    }

    public function testIteratorResetsForEachNewInput(): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState();
        $task->initialize($state);

        self::assertSame([1, 2], $this->iterate($task, $state, [1, 2]));
        self::assertSame([3, 4], $this->iterate($task, $state, [3, 4]));
    }

    public function testEmptyInputIsSkipped(): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState();
        $task->initialize($state);

        self::assertSame([], $this->iterate($task, $state, new \ArrayIterator([])));
        self::assertFalse($task->next($state));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'null' => [null];
        yield 'string' => ['foo'];
        yield 'int' => [42];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('invalidInputProvider')]
    public function testNonIterableInputThrows(mixed $input): void
    {
        $task = new InputIteratorTask();
        $state = $this->createState();
        $task->initialize($state);
        $state->setInput($input);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot create iterator from input');

        $task->execute($state);
    }

    private function iterate(InputIteratorTask $task, ProcessState $state, mixed $input): array
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

    private function createState(): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('iterate', InputIteratorTask::class, []));

        return $state;
    }
}
