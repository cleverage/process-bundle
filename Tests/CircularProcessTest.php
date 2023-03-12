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
 * Assert circular dependencies are correctly checked
 */
class CircularProcessTest extends AbstractProcessTest
{
    /**
     * @expectedException \CleverAge\ProcessBundle\Exception\CircularProcessException
     */
    public function testCircularProcess(): void
    {
        $this->processManager->execute('test.circular_process');
    }

    /**
     * @expectedException \CleverAge\ProcessBundle\Exception\CircularProcessException
     */
    public function testReversedCircularProcess(): void
    {
        $this->processManager->execute('test.circular_process.reversed');
    }

    /**
     * @expectedException \CleverAge\ProcessBundle\Exception\CircularProcessException
     */
    public function testSelfCircularProcess(): void
    {
        $this->processManager->execute('test.circular_process.self');
    }

    /**
     * A loop in an independent branch was sometime not properly detected
     *
     * @expectedException \CleverAge\ProcessBundle\Exception\CircularProcessException
     */
    public function testLongCircularProcess(): void
    {
        $this->processManager->execute('test.circular_process.long');
    }
}
