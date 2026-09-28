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

namespace CleverAge\ProcessBundle\Tests\Model;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
class ProcessStateTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function errorContextValueProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'int' => [42];
        yield 'array' => [['foo' => 'bar']];
        yield 'bool' => [true];
        yield 'float' => [1.5];
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('errorContextValueProvider')]
    public function testAddErrorContextValueAcceptsAnyValue(mixed $value): void
    {
        $state = $this->createState();

        $state->addErrorContextValue('key', $value);

        self::assertSame(['key' => $value], $state->getErrorContext());
    }

    public function testRemoveErrorContext(): void
    {
        $state = $this->createState();
        $state->addErrorContextValue('kept', 'foo');
        $state->addErrorContextValue(0, null);

        $state->removeErrorContext(0);

        self::assertSame(['kept' => 'foo'], $state->getErrorContext());
    }

    private function createState(): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);

        return new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
    }
}
