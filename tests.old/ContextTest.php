<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

/**
 * Test context replacement mechanism.
 */
class ContextTest extends AbstractProcessTest
{
    /**
     * Assert a value can correctly passed through context.
     */
    public function testSimpleContext(): void
    {
        $result = $this->processManager->execute('test.context', 'ko', [
            'value' => 'ok',
        ]);

        self::assertEquals('ok', $result);

        $result = $this->processManager->execute('test.context.sub_value', null, [
            'value' => 'ok',
        ]);

        self::assertEquals([
            'key' => 'ok',
        ], $result);
    }

    /**
     * Assert a value can correctly passed and merged into a string, through context.
     */
    public function testContextMergedValue(): void
    {
        $result = $this->processManager->execute('test.context.merged_value', null, [
            'value' => 'ok',
        ]);

        self::assertEquals('value is ok', $result);
    }

    /**
     * Assert 2 values can correctly passed and merged into a string, through context.
     */
    public function testContextMultiValue(): void
    {
        $result = $this->processManager->execute(
            'test.context.multi_values',
            null,
            [
                'value1' => 'red',
                'value2' => 'dead',
            ]
        );

        self::assertEquals('red is dead', $result);
    }

    /**
     * Assert a complex value will fail while being merged into a string, through context.
     */
    public function testContextCannotMergeValue(): void
    {
        $this->setExpectedException(\RuntimeException::class);

        $this->processManager->execute(
            'test.context.merged_value',
            null,
            [
                'value' => [
                    'another_key' => 'another_value',
                ],
            ]
        );
    }

    /**
     * Assert a complex value can correctly passed through context.
     */
    public function testComplexContext(): void
    {
        $result = $this->processManager->execute('test.context', null, [
            'value' => [
                'another_key' => 'another_value',
            ],
        ]);

        self::assertEquals([
            'another_key' => 'another_value',
        ], $result);

        $result = $this->processManager->execute(
            'test.context.sub_value',
            null,
            [
                'value' => [
                    'another_key' => 'another_value',
                ],
            ]
        );

        self::assertEquals([
            'key' => [
                'another_key' => 'another_value',
            ],
        ], $result);
    }
}
