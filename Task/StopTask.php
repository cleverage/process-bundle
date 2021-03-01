<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Allows to directly stop a process, marking it as failed
 *
 * ##### Task reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Task\StopTask`
 * * **Input**: _ignored_
 * * **Output**: _none_
 */
class StopTask implements TaskInterface
{
    /**
     * {@inheritdoc}
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $state->setStopped(true);
        $state->getProcessHistory()->setFailed();
    }
}
