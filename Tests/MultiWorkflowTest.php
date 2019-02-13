<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

/**
 * Assert the behavior of multiple-branch workflow
 */
class MultiWorkflowTest extends AbstractProcessTest
{
    public function testMultiWorkflow()
    {
        $this->processManager->execute('test.multi_workflow_process');

        $this->assertDataQueue([
            [
                'task'  => 'data',
                'value' => 1,
            ],
            [
                'task'  => 'data',
                'value' => 2,
            ],
            [
                'task'  => 'data',
                'value' => 3,
            ],
            [
                'task'  => 'aggregate',
                'value' => [1, 2, 3],
            ],
            [
                'task'  => 'aggregate2',
                'value' => [1, 2, 3],
            ],
            [
                'task'  => 'inputAggregate',
                'value' => [
                    'aggregate'  => [1, 2, 3],
                    'aggregate2' => [1, 2, 3],
                ],
            ],
        ], 'test.multi_workflow_process');
    }
}
