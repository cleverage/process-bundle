<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
        $result = $this->processManager->execute('test.simple_process', null, 'success');

        self::assertEquals('success', $result);
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
