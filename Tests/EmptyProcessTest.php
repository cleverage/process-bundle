<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

/**
 * Check empty process behaviours
 */
class EmptyProcessTest extends AbstractProcessTest
{
    /**
     * Assert an empty process do not fail
     */
    public function testEmptyProcess()
    {
        $this->processManager->execute('test.empty_process');
        self::assertTrue(true, 'There was an exception');
    }
}
