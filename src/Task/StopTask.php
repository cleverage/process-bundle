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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Exception\ProcessStoppedException;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Allows to directly stop a process, making it fail: the process manager throws a ProcessFailedException.
 *
 * @final since 5.4
 */
class StopTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
        $state->stop(
            ProcessStoppedException::createByTask(
                $state->getProcessConfiguration()->getCode(),
                $state->getTaskConfiguration()->getCode(),
            )
        );
        $state->getProcessHistory()
            ->setFailed();
    }
}
