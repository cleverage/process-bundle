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

use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class to handle output iterations
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
abstract class AbstractIterableOutputTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    /** @var \Iterator */
    protected $iterator;

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        if (null === $this->iterator) {
            $this->iterator = $this->initializeIterator($state);
        }
        $state->addErrorContextValue('iterator_key', $this->iterator->key());

        $state->setOutput($this->iterator->current());
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     *
     * @param ProcessState $state
     *
     * @return bool
     */
    public function next(ProcessState $state)
    {
        $this->iterator->next();

        $state->removeErrorContext('iterator_key');

        return $this->iterator->valid();
    }

    /**
     * @param ProcessState $state
     *
     * @return \Iterator
     */
    abstract protected function initializeIterator(ProcessState $state): \Iterator;
}
