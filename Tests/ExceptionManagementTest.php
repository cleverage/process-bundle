<?php

namespace CleverAge\ProcessBundle\Tests;

/**
 * Asset the behavior of a process when there is an error
 */
class ExceptionManagementTest extends AbstractProcessTest
{

    /**
     * Assert errors in the middle of an iteration does not skip subsequent loops and does not spam "error" flow
     */
    public function testSetExceptionInTheMiddle()
    {
        $result = $this->processManager->execute('test.exception_management.set_exception_in_the_middle');

        self::assertEquals([
            'abc',
            'bcd',
            'cde',
            'def',
        ], $result['success']);

        self::assertEquals([
            1
        ], $result['errors']);
    }
}
