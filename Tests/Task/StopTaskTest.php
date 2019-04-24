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

class StopTaskTest extends AbstractProcessTest
{

    /**
     * Assert the iteration is stopped at the right time
     */
    public function testIterableInterruption()
    {
        $this->processManager->execute('test.task.stop_task.iterable_interruption');

        $this->assertDataQueue(
            [
                [
                    'task' => 'data',
                    'value' => 1,
                ],
            ],
            'test.task.stop_task.iterable_interruption'
        );
    }
}
