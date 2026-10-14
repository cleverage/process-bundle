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
use CleverAge\ProcessBundle\Task\UnavailableTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnavailableTask::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(ProcessHistory::class)]
#[UsesClass(ProcessState::class)]
class UnavailableTaskTest extends TestCase
{
    public function testExecuteThrowsTheMessage(): void
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The serializer is not enabled');

        (new UnavailableTask('The serializer is not enabled'))->execute($state);
    }
}
