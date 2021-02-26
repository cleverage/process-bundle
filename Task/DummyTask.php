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
 * Dummy task that pass the input to the output
 *
 * Passes the input to the output, can be used as an entry point allow multiple tasks to be run at the entry point
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\Debug\DummyTask`
 *  * **Input**: `any`
 *  * **Output**: `any`, re-output given input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DummyTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $state->setOutput($state->getInput());
    }
}
