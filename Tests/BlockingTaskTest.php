<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
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
    public function testSimpleBlocking(): void
    {
        $result = $this->processManager->execute('test.simple_blocking');

        self::assertEquals([1, 2, 3], $result);
    }

    public function testBlockingSolo(): void
    {
        $result = $this->processManager->execute('test.blocking_solo', 'success');

        self::assertEquals(['success'], $result);
    }

    public function testMultipleBlockingSolo(): void
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
    public function testMultipleBlocking(): void
    {
        $result = $this->processManager->execute('test.multiple_blocking');

        self::assertEquals([1, 2, 3], $result);
    }

    /**
     * Assert when there is multiple iterations before a blocking that all are successfully resolved, and the blocking
     * is executed only once
     */
    public function testMultipleIterationBlocking(): void
    {
        $this->processManager->execute('test.multiple_iteration_blocking');

        $this->assertDataQueue(
            [
                [
                    'task' => 'aggregate',
                    'value' => [1, 2, 3, 1, 2, 3, 1, 2, 3],
                ],
            ],
            'test.multiple_iteration_blocking'
        );
    }

    /**
     * Assert that if a blocking is never executed, it will automatically skip subsequent tasks
     */
    public function testBlockingEmptyData(): void
    {
        $this->processManager->execute('test.blocking_empty_data');

        $this->assertDataQueue([], 'test.blocking_empty_data');
    }
}
