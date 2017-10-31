<?php

namespace CleverAge\ProcessBundle\Tests;

use Symfony\Component\Console\Output\BufferedOutput;

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