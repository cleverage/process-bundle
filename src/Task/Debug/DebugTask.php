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
use Symfony\Component\VarDumper\VarDumper;

/**
 * Dump the content of the input.
 */
class DebugTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
        if (class_exists(VarDumper::class)) {
            VarDumper::dump($state->getInput());
        }

        $state->setOutput($state->getInput());
    }
}
