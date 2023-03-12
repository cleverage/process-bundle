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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Dummy task that pass the input to the output
 */
class DummyTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
        $state->setOutput($state->getInput());
    }
}
