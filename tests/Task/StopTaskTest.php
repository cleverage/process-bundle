<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\StopTask;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(StopTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class StopTaskTest extends TestCase
{
    public function testProcessIsStoppedAndMarkedAsFailed(): void
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setInput(['ignored']);
        $state->reset(false);
        self::assertFalse($state->isStopped());
        self::assertFalse($state->getProcessHistory()->isFailed());

        (new StopTask())->execute($state);

        self::assertTrue($state->isStopped());
        self::assertTrue($state->getProcessHistory()->isFailed());
        self::assertSame(ProcessHistory::STATE_FAILED, $state->getProcessHistory()->getState());
        self::assertNotNull($state->getProcessHistory()->getEndDate());
        self::assertNull($state->getOutput());
        self::assertNull($state->getException());
    }
}
