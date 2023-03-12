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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Iterator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

/**
 * Base class to handle output iterations
 */
abstract class AbstractIterableOutputTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected ?Iterator $iterator = null;

    public function execute(ProcessState $state): void
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
     */
    public function next(ProcessState $state): bool
    {
        if (! $this->iterator) {
            return false;
        }
        $this->iterator->next();

        $state->removeErrorContext('iterator_key');

        if (! $this->iterator->valid()) {
            // Reset the iterator to allow the following iteration
            $this->iterator = null;

            return false;
        }

        return true;
    }

    /**
     * Create or recreate an iterator from input
     */
    protected function handleIteratorFromInput(ProcessState $state): void
    {
        if ($this->iterator instanceof Iterator) {
            if ($this->iterator->valid()) {
                return; // No action needed, execution is in progress
            }
            // Cleanup invalid iterator => prepare for new iteration cycle
            $this->iterator = null;
        }

        // This should never be reached
        /** @phpstan-ignore-next-line */
        if ($this->iterator !== null) {
            throw new UnexpectedValueException(
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

    abstract protected function initializeIterator(ProcessState $state): Iterator;
}
