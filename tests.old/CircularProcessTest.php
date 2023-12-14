<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */
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
 * Assert circular dependencies are correctly checked.
 */
class CircularProcessTest extends AbstractProcessTest
{
    public function testCircularProcess(): void
    {
        $this->setExpectedException(\CleverAge\ProcessBundle\Exception\CircularProcessException::class);

        $this->processManager->execute('test.circular_process');
    }

    public function testReversedCircularProcess(): void
    {
        $this->setExpectedException(\CleverAge\ProcessBundle\Exception\CircularProcessException::class);

        $this->processManager->execute('test.circular_process.reversed');
    }

    public function testSelfCircularProcess(): void
    {
        $this->setExpectedException(\CleverAge\ProcessBundle\Exception\CircularProcessException::class);

        $this->processManager->execute('test.circular_process.self');
    }

    /**
     * A loop in an independent branch was sometime not properly detected.
     */
    public function testLongCircularProcess(): void
    {
        $this->setExpectedException(\CleverAge\ProcessBundle\Exception\CircularProcessException::class);

        $this->processManager->execute('test.circular_process.long');
    }
}
