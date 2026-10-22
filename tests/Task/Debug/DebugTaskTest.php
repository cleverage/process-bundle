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

namespace CleverAge\ProcessBundle\Tests\Task\Debug;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Debug\DebugTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\VarDumper;

#[\PHPUnit\Framework\Attributes\CoversClass(DebugTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class DebugTaskTest extends TestCase
{
    /** @var list<mixed> */
    private array $dumped = [];

    private ?\Closure $previousHandler = null;

    protected function setUp(): void
    {
        $this->dumped = [];
        $previousHandler = VarDumper::setHandler(function (mixed $var): void {
            $this->dumped[] = $var;
        });
        $this->previousHandler = null === $previousHandler ? null : \Closure::fromCallable($previousHandler);
    }

    protected function tearDown(): void
    {
        VarDumper::setHandler($this->previousHandler);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function inputProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'int' => [42];
        yield 'array' => [['id' => 123, 'firstname' => 'Test1']];
        yield 'object' => [new \ArrayObject(['foo' => 'bar'])];
        yield 'null' => [null];
    }

    #[DataProvider('inputProvider')]
    public function testInputIsDumpedAndForwardedToTheOutput(mixed $input): void
    {
        $state = $this->createState($input);

        (new DebugTask())->execute($state);

        self::assertSame([$input], $this->dumped);
        self::assertSame($input, $state->getOutput());
        self::assertFalse($state->isSkipped());
    }

    public function testEachExecutionDumpsItsOwnInput(): void
    {
        $task = new DebugTask();
        $outputs = [];
        foreach (['a', 'b'] as $input) {
            $state = $this->createState($input);
            $task->execute($state);
            $outputs[] = $state->getOutput();
        }

        self::assertSame(['a', 'b'], $this->dumped);
        self::assertSame(['a', 'b'], $outputs);
    }

    private function createState(mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->reset(false);
        $state->setInput($input);

        return $state;
    }
}
