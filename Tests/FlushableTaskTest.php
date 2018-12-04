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
 * Assert basic behavior for flushable tasks (using the aggregator implementation)
 */
class FlushableTaskTest extends AbstractProcessTest
{
    /**
     * @throws \Exception
     */
    public function testSimpleFlushable()
    {
        $result = $this->processManager->execute('test.simple_flushable');

        self::assertCount(2, $result);
        self::assertEquals([1, 2], $result[0]);
        self::assertEquals([3], $result[1]);
    }

    /**
     * @throws \Exception
     */
    public function testSingleFlushable()
    {
        $result = $this->processManager->execute('test.single_flushable');

        self::assertCount(1, $result);
        self::assertEquals([1], $result[0]);
    }

    /**
     * @throws \Exception
     */
    public function testSimpleFlushableNoIterable()
    {
        $result = $this->processManager->execute('test.simple_flushable_no_iterable');

        self::assertCount(1, $result);
        self::assertEquals([1], $result[0]);
    }

    /**
     * @throws \Exception
     */
    public function testIterableFlushable()
    {
        $result = $this->processManager->execute('test.iterable_flushable');

        self::assertEquals([1, 2, 3, 1, 2, 3], $result);
    }
}
