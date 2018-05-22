<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * This is a dummy task mostly intended for testing purpose.
 * Forward any input to the error output
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ErrorForwarderTask implements TaskInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ProcessState $state)
    {
        $state->setSkipped(true);
        $state->setError($state->getInput());
    }
}
