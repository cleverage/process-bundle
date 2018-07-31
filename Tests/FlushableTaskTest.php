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
    public function testSimpleBlocking()
    {
        $result = $this->processManager->execute('test.simple_flushable');

        self::assertCount(2, $result);
        self::assertEquals([1, 2], $result[0]);
        self::assertEquals([3], $result[1]);
    }
}
