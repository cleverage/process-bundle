<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
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
     * Check that a known process can be executed and return a 0 code
     */
    public function testSimpleProcess()
    {
        $result = $this->processManager->execute('test.simple_process');

        self::assertEquals(0, $result);
    }

    /**
     * Check the execution order of a process containing one iterable loop and a blocking task
     */
    public function testIterableProcess()
    {
        $this->processManager->execute('test.iterable_process');

        $this->assertDataQueue(
            [
                [
                    'task'  => 'data',
                    'value' => 1,
                ],
                [
                    'task'  => 'doNothing',
                    'value' => 1,
                ],
                [
                    'task'  => 'data',
                    'value' => 2,
                ],
                [
                    'task'  => 'doNothing',
                    'value' => 2,
                ],
                [
                    'task'  => 'data',
                    'value' => 3,
                ],
                [
                    'task'  => 'doNothing',
                    'value' => 3,
                ],
                [
                    'task'  => 'data',
                    'value' => 4,
                ],
                [
                    'task'  => 'doNothing',
                    'value' => 4,
                ],
                [
                    'task'  => 'aggregate',
                    'value' => [1, 2, 3, 4],
                ],
            ], 'test.iterable_process');
    }
}
