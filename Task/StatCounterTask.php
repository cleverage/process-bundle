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

use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LogLevel;

/**
 * Count the number of times the task was executed
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class StatCounterTask implements FinalizableTaskInterface
{
    /** @var int */
    protected $counter = 0;

    /**
     * @param ProcessState $state
     */
    public function finalize(ProcessState $state)
    {
        $state->log("Processed item count: {$this->counter}", LogLevel::INFO);
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $this->counter++;
    }
}
