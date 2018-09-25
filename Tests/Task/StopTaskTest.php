<?php

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
                    'task'  => 'data',
                    'value' => 1,
                ],
            ], 'test.task.stop_task.iterable_interruption');
    }
}
