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
use CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ErrorForwarderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class ErrorForwarderTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function inputProvider(): iterable
    {
        yield 'string' => ['Error 1'];
        yield 'int' => [42];
        yield 'array' => [['id' => 123]];
        yield 'object' => [new \ArrayObject(['foo' => 'bar'])];
        yield 'null' => [null];
    }

    #[DataProvider('inputProvider')]
    public function testInputIsForwardedToTheErrorOutputAndTaskIsSkipped(mixed $input): void
    {
        $state = $this->execute($input);

        self::assertTrue($state->isSkipped());
        self::assertTrue($state->hasErrorOutput());
        self::assertSame($input, $state->getErrorOutput());
        self::assertNull($state->getOutput());
        self::assertNull($state->getException());
        self::assertFalse($state->isStopped());
    }

    private function execute(mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->reset(false);
        $state->setInput($input);

        (new ErrorForwarderTask())->execute($state);

        return $state;
    }
}
