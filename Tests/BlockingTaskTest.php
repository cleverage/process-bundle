<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

/**
 * Assert basic behavior for blocking tasks (using the aggregator implementation)
 */
class BlockingTaskTest extends AbstractProcessTest
{

    public function testSimpleBlocking()
    {
        $result = $this->processManager->execute('test.simple_blocking');

        self::assertEquals([1, 2, 3], $result);
    }

    public function testBlockingSolo()
    {
        $result = $this->processManager->execute('test.blocking_solo', 'success');

        self::assertEquals(['success'], $result);
    }

    public function testMultipleBlockingSolo()
    {
        $result = $this->processManager->execute('test.multiple_blocking_solo', 'success');

        self::assertEquals([['success']], $result);
    }

    /**
     * Assert a process with multiple blocking tasks can execute properly.
     * Check
     *  - a subsequent blocking task will be proceeded at least once
     *  - a subsequent blocking task will be proceeded at most once
     */
    public function testMultipleBlocking()
    {
        $result = $this->processManager->execute('test.multiple_blocking');

        self::assertEquals([1, 2, 3], $result);
    }
}
