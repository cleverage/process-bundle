<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
