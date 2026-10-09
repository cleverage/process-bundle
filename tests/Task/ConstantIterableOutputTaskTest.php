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
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(ConstantIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class ConstantIterableOutputTaskTest extends TestCase
{
    public function testEachValueIsOutputInOrder(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => ['id' => 123, 'firstname' => 'Test1', 'lastname' => 'Test2']]);
        $task->initialize($state);

        self::assertSame([123, 'Test1', 'Test2'], $this->iterate($task, $state));
    }

    public function testIterationKeyIsSetInErrorContext(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => ['a' => 'foo', 'b' => 'bar']]);
        $task->initialize($state);

        $task->execute($state);
        self::assertSame(['iterator_key' => 'a'], $state->getErrorContext());
        self::assertTrue($task->next($state));

        $task->execute($state);
        self::assertSame(['iterator_key' => 'b'], $state->getErrorContext());
        self::assertFalse($task->next($state));
        self::assertSame([], $state->getErrorContext());
    }

    public function testInputIsIgnored(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => ['foo', 'bar']], ['ignored', 'input', 'values']);
        $task->initialize($state);

        self::assertSame(['foo', 'bar'], $this->iterate($task, $state));
    }

    public function testIterationRestartsOnNextInput(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => ['foo', 'bar']], 'first');
        $task->initialize($state);

        self::assertSame(['foo', 'bar'], $this->iterate($task, $state));

        $state->setInput('second');
        self::assertSame(['foo', 'bar'], $this->iterate($task, $state));
    }

    public function testEmptyOutputIsSkipped(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => []]);
        $task->initialize($state);

        $task->execute($state);

        self::assertTrue($state->isSkipped());
        self::assertFalse($task->next($state));
    }

    public function testNonArrayOutputIsRejected(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState(['output' => 'foo']);

        $this->expectException(InvalidOptionsException::class);
        $task->initialize($state);
    }

    public function testMissingOutputOptionIsRejected(): void
    {
        $task = new ConstantIterableOutputTask();
        $state = $this->createState([]);

        $this->expectException(MissingOptionsException::class);
        $task->initialize($state);
    }

    /**
     * Mimic the process manager loop on an iterable task.
     *
     * @return list<mixed>
     */
    private function iterate(ConstantIterableOutputTask $task, ProcessState $state): array
    {
        $outputs = [];
        do {
            $state->setSkipped(false);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $outputs[] = $state->getOutput();
            }
        } while ($task->next($state));

        return $outputs;
    }

    private function createState(array $options, mixed $input = null): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('constant', ConstantIterableOutputTask::class, $options));
        $state->setInput($input);
        $state->reset(false);

        return $state;
    }
}
