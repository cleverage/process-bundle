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
 * Assert multiple branch processes are correctly checked
 */
class MultiBranchProcessTest extends AbstractProcessTest
{
    /**
     * Assert only one branch is called
     */
    public function testMultiBranchProcess()
    {
        $this->processManager->execute('test.multi_branch_process_first');
        $this->assertDataQueue([
            [
                'task'  => 'data1',
                'value' => 'ok',
            ],
        ], 'test.multi_branch_process_first');

        $this->processManager->execute('test.multi_branch_process_entry');
        $this->assertDataQueue([
            [
                'task'  => 'data2',
                'value' => 'ok',
            ],
        ], 'test.multi_branch_process_entry');

        $this->processManager->execute('test.multi_branch_process_entry_reversed');
        $this->assertDataQueue([
            [
                'task'  => 'data2',
                'value' => 'ok',
            ],
        ], 'test.multi_branch_process_entry');

        $this->processManager->execute('test.multi_branch_process_end');
        $this->assertDataQueue([
            [
                'task'  => 'data2',
                'value' => 'ok',
            ],
        ], 'test.multi_branch_process_end');

        $this->processManager->execute('test.multi_branch_process_entry_end');
        $this->assertDataQueue([
            [
                'task'  => 'data2',
                'value' => 'ok',
            ],
        ], 'test.multi_branch_process_entry_end');
    }

    public function testMainGroupOrder()
    {
        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_first');
        self::assertEquals(['data1', 'pushDataEvent1'], $process->getMainTaskGroup(),'Failed testing task order with process test.multi_branch_process_first');

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_entry');
        self::assertEquals(['data2', 'pushDataEvent2'], $process->getMainTaskGroup(),'Failed testing task order with process test.multi_branch_process_entry');

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_entry_reversed');
        self::assertEquals(['data2', 'pushDataEvent2'], $process->getMainTaskGroup(),'Failed testing task order with process test.multi_branch_process_entry_reversed');

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_end');
        self::assertEquals(['data2', 'pushDataEvent2'], $process->getMainTaskGroup(),'Failed testing task order with process test.multi_branch_process_end');

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_entry_end');
        self::assertEquals(['data2', 'pushDataEvent2'], $process->getMainTaskGroup(),'Failed testing task order with process test.multi_branch_process_entry_end');
    }

    /**
     * Assert a task cannot be started if the process is not valid
     *
     * @expectedException \CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException
     */
    public function testMultiBranchProcessError()
    {
        $this->processManager->execute('test.multi_branch_process_entry_end_error');
    }
}
