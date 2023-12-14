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

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the ProcessExecutorTask.
 */
class ProcessExecutorTaskTest extends AbstractProcessTest
{
    /**
     * Assert the process executor correctly chain the input/output.
     */
    public function testExecutor(): void
    {
        $result = $this->processManager->execute('test.process_execute_task');
        self::assertEquals([1, 2, 3, 4], $result);
    }

    /**
     * Assert correct error if it doesn't match a good subprocess name.
     */
    public function testExecutorError(): void
    {
        $this->setExpectedException(\RuntimeException::class);

        $this->processManager->execute('test.process_execute_task.error');
    }
}
