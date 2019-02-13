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
