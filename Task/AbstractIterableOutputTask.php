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

use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
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
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $this->handleIteratorFromInput($state);

        $state->addErrorContextValue('iterator_key', $this->iterator->key());

        if ($this->iterator->valid()) {
            $state->setOutput($this->iterator->current());
        } else {
            $state->setSkipped(true);
            $this->iterator = null;
        }
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
        if (!$this->iterator) {
            return false;
        }
        $this->iterator->next();

        $state->removeErrorContext('iterator_key');

        return $this->iterator->valid();
    }
    /**
     * Create or recreate an iterator from input
     *
     * @param ProcessState $state
     */
    protected function handleIteratorFromInput(ProcessState $state)
    {
        if ($this->iterator instanceof \Iterator) {
            if ($this->iterator->valid()) {
                return; // No action needed, execution is in progress
            }
            // Cleanup invalid iterator => prepare for new iteration cycle
            $this->iterator = null;
        }

        // This should never be reached
        if (null !== $this->iterator) {
            throw new \UnexpectedValueException(
                "At this point iterator should have been null, maybe it's a wrong type..."
            );
        }

        $this->iterator = $this->initializeIterator($state);
    }

    /**
     * Allow to not implement this method, not required by most tasks, removing inheritance would break back-compat
     *
     * @inheritDoc
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * @param ProcessState $state
     *
     * @return \Iterator
     */
    abstract protected function initializeIterator(ProcessState $state): \Iterator;
}
