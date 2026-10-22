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
use CleverAge\ProcessBundle\Task\DummyTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(DummyTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class DummyTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function inputProvider(): iterable
    {
        yield 'array' => [['id' => 123]];
        yield 'object' => [new \stdClass()];
        yield 'string' => ['foo'];
        yield 'int' => [0];
        yield 'empty array' => [[]];
        yield 'null' => [null];
    }

    #[DataProvider('inputProvider')]
    public function testInputIsPassedToOutputUnchanged(mixed $input): void
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setInput($input);
        $state->reset(false);

        (new DummyTask())->execute($state);

        self::assertSame($input, $state->getOutput());
        self::assertFalse($state->isSkipped());
        self::assertFalse($state->isStopped());
    }
}
