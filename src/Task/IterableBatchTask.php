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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A Batch task that iterate on flush
 * It's mainly an example task since it's not useful as-is, but the processInput method may allow custom overrides.
 */
class IterableBatchTask extends AbstractConfigurableTask implements FlushableTaskInterface, IterableTaskInterface
{
    protected ?\SplQueue $outputQueue = null;

    protected bool $flushMode = false;

    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    public function initialize(ProcessState $state): void
    {
        parent::initialize($state);
        $this->outputQueue = new \SplQueue();
    }

    public function flush(ProcessState $state): void
    {
        $this->flushMode = true;
        if ($this->outputQueue->isEmpty()) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->outputQueue->dequeue());
        }
    }

    public function execute(ProcessState $state): void
    {
        $batchCount = $this->getOption($state, 'batch_count');

        // Register new input
        if (!$this->flushMode) {
            $this->outputQueue->enqueue($this->processInput($state));
        }

        // Detect flushing
        if (null !== $batchCount && ($this->outputQueue instanceof \SplQueue ? \count($this->outputQueue) : 0) >= $batchCount) {
            $this->flushMode = true;
        }

        // Flush or skip
        if ($this->flushMode) {
            $state->setOutput($this->outputQueue->dequeue());
        } else {
            $state->setSkipped(true);
        }
    }

    public function next(ProcessState $state): bool
    {
        // Stop flushing once over
        if (($this->outputQueue instanceof \SplQueue ? \count($this->outputQueue) : 0) === 0) {
            $this->flushMode = false;
        }

        return $this->flushMode;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'batch_count' => 10,
        ]);

        $resolver->setAllowedTypes('batch_count', 'integer');
    }

    /**
     * Override this method to add a custom processing behavior.
     */
    protected function processInput(ProcessState $state): mixed
    {
        return $state->getInput();
    }
}
