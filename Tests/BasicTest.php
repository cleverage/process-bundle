<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

/**
 * Test the basic behavior of the process
 */
class BasicTest extends AbstractProcessTest
{

    /**
     * Check that an unknown process produce the right error
     *
     * @expectedException \CleverAge\ProcessBundle\Exception\MissingProcessException
     */
    public function testUnknownProcess()
    {
        $this->processManager->execute('test.unknown_test');
    }

    /**
     * Check that a known process can be executed and return defined output
     */
    public function testSimpleProcess()
    {
        $result = $this->processManager->execute('test.simple_process', 'success');

        self::assertEquals('success', $result);
    }

    /**
     * Assert that the error branch is not called
     */
    public function testErrorProcess()
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
     * Assert that the error branch is called, and blocking task are correctly working
     */
    public function testErrorProcessBlocking()
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
}
