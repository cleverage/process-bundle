<?php
namespace CleverAge\ProcessBundle\Tests;

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
}