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

namespace CleverAge\ProcessBundle\Tests\Task;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the ProcessExecutorTask
 */
class ProcessExecutorTaskTest extends AbstractProcessTest
{
    /**
     * Assert the process executor correctly chain the input/output
     */
    public function testExecutor()
    {
        $result = $this->processManager->execute('test.process_execute_task');
        self::assertEquals([1, 2, 3, 4], $result);
    }

    /**
     * Assert correct error if it doesn't match a good subprocess name
     *
     * @expectedException \RuntimeException
     */
    public function testExecutorError()
    {
        $this->processManager->execute('test.process_execute_task.error');
    }
}
