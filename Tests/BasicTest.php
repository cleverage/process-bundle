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

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test the basic behavior of the process
 */
class BasicTest extends AbstractProcessTest
{

    /**
     * @expectedException \CleverAge\ProcessBundle\Exception\MissingProcessException
     */
    public function testUnknownProcess()
    {
        $this->processManager->execute('test.unknown_test');
    }

    public function testSimpleProcess()
    {
        $result = $this->processManager->execute('test.simple_process');

        self::assertEquals(0, $result);
    }

    public function testIterableProcess()
    {
        $output = new BufferedOutput();
        $result = $this->processManager->execute('test.iterable_process', $output);
        self::assertEquals(0, $result);
        $this->assertDataQueue([1, 1, 2, 2, 3, 3, 4, 4, [1, 2, 3, 4]]);
    }
}