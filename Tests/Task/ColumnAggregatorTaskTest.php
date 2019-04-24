<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

class ColumnAggregatorTaskTest extends AbstractProcessTest
{
    public function testSimpleColumnAggregation()
    {
        $input1 = ['col1' => 'A', 'col2' => 'val1'];
        $input2 = ['col1' => 'B', 'col2' => 'val2'];
        $input3 = ['col1' => 'A', 'col2' => 'val3'];
        $input4 = ['col1' => 'B', 'col2' => 'val4'];
        $input = [$input1, $input2, $input3, $input4];

        self::assertEquals(
            [
                'aggregateAny' => [
                    'col1' => [
                        'column' => 'col1',
                        'values' => $input,
                    ],
                ],
                'aggregateA' => [
                    'col1' => [
                        'column' => 'col1',
                        'values' => [$input1, $input3],
                    ],
                ],
                'aggregateB' => [
                    'col1' => [
                        'column' => 'col1',
                        'values' => [$input2, $input4],
                    ],
                ],
            ],
            $this->processManager->execute('test.column_aggregator_task.simple', $input)
        );

    }
}
