<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */
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
 * Test the basic behavior of the process.
 */
class BasicTest extends AbstractProcessTest
{
    /**
     * Check that an unknown process produce the right error.
     */
    public function testUnknownProcess(): void
    {
        $this->setExpectedException(\CleverAge\ProcessBundle\Exception\MissingProcessException::class);

        $this->processManager->execute('test.unknown_test');
    }

    /**
     * Check that a known process can be executed and return defined output.
     */
    public function testSimpleProcess(): void
    {
        $result = $this->processManager->execute('test.simple_process', 'success');

        self::assertEquals('success', $result);
    }

    /**
     * Assert that the error branch is not called.
     */
    public function testErrorProcess(): void
    {
        $this->processManager->execute('test.error_process');
        $this->assertDataQueue(
            [
                [
                    'task' => 'doNothing',
                    'value' => 1,
                ],
                [
                    'task' => 'doNothing',
                    'value' => 2,
                ],
                [
                    'task' => 'doNothing',
                    'value' => 3,
                ],
            ],
            'test.error_process'
        );
    }

    /**
     * Assert that the error branch is called, and blocking task are correctly working.
     */
    public function testErrorProcessBlocking(): void
    {
        $this->processManager->execute('test.error_process_with_blocking');
        $this->assertDataQueue(
            [
                [
                    'task' => 'doNothing2',
                    'value' => 1,
                ],
                [
                    'task' => 'doNothing2',
                    'value' => 2,
                ],
                [
                    'task' => 'doNothing2',
                    'value' => 3,
                ],
                [
                    'task' => 'aggregate',
                    'value' => [1, 2, 3],
                ],
            ],
            'test.error_process_with_blocking'
        );
    }

    public function testFailingEntryPointWithAncestors(): void
    {
        $this->setExpectedException(\CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException::class);

        $this->processManager->execute('test.entry_point_with_ancestor');
    }

    /**
     * Check that the use of a string in task "outputs" or "errors" is possible.
     */
    public function testStringOutput(): void
    {
        $result = $this->processManager->execute('test.string_outputs', 'success');
        self::assertEquals('success', $result);

        $result = $this->processManager->execute('test.string_errors', 'success');
        self::assertEquals('success', $result);
    }
}
