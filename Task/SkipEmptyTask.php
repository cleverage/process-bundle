<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Allow to skip the execution on empty input.
 * Useful when combined with an aggregator task
 */
class SkipEmptyTask implements TaskInterface
{
    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $state->setOutput($state->getInput());
        if (empty($state->getInput())) {
            $state->setSkipped(true);
        }
    }
}
