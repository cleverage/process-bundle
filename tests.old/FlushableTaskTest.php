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
 * Assert basic behavior for flushable tasks (using the aggregator implementation).
 */
class FlushableTaskTest extends AbstractProcessTest
{
    public function testSimpleFlushable(): void
    {
        $result = $this->processManager->execute('test.simple_flushable');

        self::assertCount(2, $result);
        self::assertEquals([1, 2], $result[0]);
        self::assertEquals([3], $result[1]);
    }

    public function testSingleFlushable(): void
    {
        $result = $this->processManager->execute('test.single_flushable');

        self::assertCount(1, $result);
        self::assertEquals([1], $result[0]);
    }

    public function testSimpleFlushableNoIterable(): void
    {
        $result = $this->processManager->execute('test.simple_flushable_no_iterable');

        self::assertCount(1, $result);
        self::assertEquals([1], $result[0]);
    }

    public function testIterableFlushable(): void
    {
        $result = $this->processManager->execute('test.iterable_flushable');

        self::assertEquals([1, 2, 3, 1, 2, 3], $result);
    }
}
