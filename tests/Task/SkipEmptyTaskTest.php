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
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\SkipEmptyTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(SkipEmptyTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class SkipEmptyTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyInputProvider(): iterable
    {
        yield 'null' => [null];
        yield 'false' => [false];
        yield 'int zero' => [0];
        yield 'float zero' => [0.0];
        yield 'string zero' => ['0'];
        yield 'empty string' => [''];
        yield 'empty array' => [[]];
    }

    #[DataProvider('emptyInputProvider')]
    public function testEmptyInputIsSkipped(mixed $input): void
    {
        $state = $this->execute($input);

        self::assertTrue($state->isSkipped());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonEmptyInputProvider(): iterable
    {
        yield 'true' => [true];
        yield 'int' => [1];
        yield 'string' => ['foo'];
        yield 'blank string' => [' '];
        yield 'array' => [['foo']];
        yield 'array of empty value' => [[null]];
        yield 'empty object' => [new \stdClass()];
    }

    #[DataProvider('nonEmptyInputProvider')]
    public function testNonEmptyInputIsPassedToOutput(mixed $input): void
    {
        $state = $this->execute($input);

        self::assertFalse($state->isSkipped());
        self::assertSame($input, $state->getOutput());
    }

    private function execute(mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setInput($input);
        $state->reset(false);

        (new SkipEmptyTask())->execute($state);

        return $state;
    }
}
