<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Assert the correct behavior of the filter task
 */
class FilterTaskTest extends AbstractProcessTest
{

    /**
     * Assert simple matching/empty filters
     */
    public function testFilterMatch()
    {
        $input = [
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => ['something'],
            ],
            [
                'key1' => 'value1b',
                'key2' => 'value2b',
                'key3' => ['something'],
            ],
            [
                'key1' => 'value1c',
                'key2' => 'value2c',
                'key3' => [],
            ],
        ];
        $result = $this->processManager->execute('test.filter_task.match', null, $input);
        self::assertEquals([$input[0]], $result);

        $result = $this->processManager->execute('test.filter_task.not_match', null, $input);
        self::assertEquals([$input[1], $input[2]], $result);

        $result = $this->processManager->execute('test.filter_task.empty', null, $input);
        self::assertEquals([$input[2]], $result);

        $result = $this->processManager->execute('test.filter_task.not_empty', null, $input);
        self::assertEquals([$input[0], $input[1]], $result);
    }
}
