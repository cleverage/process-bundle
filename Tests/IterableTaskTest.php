<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;


class IterableTaskTest extends AbstractProcessTest
{

    /**
     * Check the execution order of a process containing one iterable loop and a blocking task
     */
    public function testIterableProcess()
    {
        $this->processManager->execute('test.iterable_process');

        $this->assertDataQueue(
            [
                [
                    'task' => 'data',
                    'value' => 1,
                ],
                [
                    'task' => 'doNothing',
                    'value' => 1,
                ],
                [
                    'task' => 'data',
                    'value' => 2,
                ],
                [
                    'task' => 'doNothing',
                    'value' => 2,
                ],
                [
                    'task' => 'data',
                    'value' => 3,
                ],
                [
                    'task' => 'doNothing',
                    'value' => 3,
                ],
                [
                    'task' => 'data',
                    'value' => 4,
                ],
                [
                    'task' => 'doNothing',
                    'value' => 4,
                ],
                [
                    'task' => 'aggregate',
                    'value' => [1, 2, 3, 4],
                ],
            ],
            'test.iterable_process'
        );
    }

    /**
     * Assert 2 iterators can run alone, without a subsequent blocking task
     * Assert \CleverAge\ProcessBundle\Task\InputIteratorTask can correctly reset
     */
    public function testDoubleIterableAlone()
    {
        $this->processManager->execute('test.double_iterable_alone');

        $this->assertDataQueue(
            [
                [
                    'task' => 'iterate',
                    'value' => 1,
                ],
                [
                    'task' => 'iterate',
                    'value' => 2,
                ],
                [
                    'task' => 'iterate',
                    'value' => 3,
                ],
                [
                    'task' => 'iterate',
                    'value' => 4,
                ],
            ],
            'test.double_iterable_alone'
        );
    }


    /**
     * Assert the SplitJoinLineTask works the way it's supposed to
     */
    public function testSplitJoinLine()
    {
        $this->processManager->execute('test.split_join_iterable');

        $this->assertDataQueue(
            [
                [
                    'product' => 'toto',
                    'asset' => 'a',
                ],
                [
                    'product' => 'toto',
                    'asset' => 'b',
                ],
                [
                    'product' => 'toto',
                    'asset' => 'c',
                ],
                [
                    'product' => 'toto',
                    'asset' => 'd',
                ],
                [
                    'product' => 'tata',
                    'asset' => 'e',
                ],
                [
                    'product' => 'tata',
                    'asset' => 'f',
                ],
                [
                    'product' => 'tata',
                    'asset' => 'g',
                ],
                [
                    'product' => 'tata',
                    'asset' => 'h',
                ],
            ],
            'test.split_join_iterable'
        );
    }
}
