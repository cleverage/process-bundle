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
        $this->assertDataQueue(
            [
                [
                    'task' => 'data1',
                    'value' => 'ok',
                ],
            ],
            'test.multi_branch_process_first'
        );

        $this->processManager->execute('test.multi_branch_process_entry');
        $this->assertDataQueue(
            [
                [
                    'task' => 'data2',
                    'value' => 'ok',
                ],
            ],
            'test.multi_branch_process_entry'
        );

        $this->processManager->execute('test.multi_branch_process_entry_reversed');
        $this->assertDataQueue(
            [
                [
                    'task' => 'data2',
                    'value' => 'ok',
                ],
            ],
            'test.multi_branch_process_entry'
        );

        $this->processManager->execute('test.multi_branch_process_end');
        $this->assertDataQueue(
            [
                [
                    'task' => 'data2',
                    'value' => 'ok',
                ],
            ],
            'test.multi_branch_process_end'
        );

        $this->processManager->execute('test.multi_branch_process_entry_end');
        $this->assertDataQueue(
            [
                [
                    'task' => 'data2',
                    'value' => 'ok',
                ],
            ],
            'test.multi_branch_process_entry_end'
        );
    }

    public function testMainGroupOrder()
    {
        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_first');
        self::assertEquals(
            ['data1', 'pushDataEvent1'],
            $process->getMainTaskGroup(),
            'Failed testing task order with process test.multi_branch_process_first'
        );

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_entry');
        self::assertEquals(
            ['data2', 'pushDataEvent2'],
            $process->getMainTaskGroup(),
            'Failed testing task order with process test.multi_branch_process_entry'
        );

        $process = $this->processConfigurationRegistry->getProcessConfiguration(
            'test.multi_branch_process_entry_reversed'
        );
        self::assertEquals(
            ['data2', 'pushDataEvent2'],
            $process->getMainTaskGroup(),
            'Failed testing task order with process test.multi_branch_process_entry_reversed'
        );

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_end');
        self::assertEquals(
            ['data2', 'pushDataEvent2'],
            $process->getMainTaskGroup(),
            'Failed testing task order with process test.multi_branch_process_end'
        );

        $process = $this->processConfigurationRegistry->getProcessConfiguration('test.multi_branch_process_entry_end');
        self::assertEquals(
            ['data2', 'pushDataEvent2'],
            $process->getMainTaskGroup(),
            'Failed testing task order with process test.multi_branch_process_entry_end'
        );
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
