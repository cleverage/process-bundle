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


class MultiWorkflowTest extends AbstractProcessTest
{
    public function testMultiWorkflow()
    {
        try {
            $this->processManager->execute('test.multi_workflow_process');
        } catch (\RuntimeException $e) {
            // TODO remove this try/catch
        }
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
