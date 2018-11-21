<?php

namespace CleverAge\ProcessBundle\Tests;


class ExceptionManagementTest extends AbstractProcessTest
{
    public function testSetExceptionInTheMiddle()
    {
        $result = $this->processManager->execute('test.exception_management.set_exception_in_the_middle');

        self::assertEquals([
            'abc',
            'bcd',
            'cde',
            'def',
        ], $result);
    }
}
