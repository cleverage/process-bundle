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

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * This is a dummy task mostly intended for testing purpose.
 * Forward any input to the error output.
 */
class ErrorForwarderTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
        $state->setSkipped(true);
        $state->setErrorOutput($state->getInput());
    }
}
