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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Accumulate inputs and periodically flush them using iterations.
 * 
 * A Batch task that iterate on flush.
 * It's mainly an example task since it's not useful as-is, but the processInput method may allow custom overrides.
 *
 * ##### Task reference
 * 
 * * **Service**: `CleverAge\ProcessBundle\Task\IterableBatchTask`
 * * **Iterable task**
 * * **Flushable task**
 * * **Input**: `any`
 * * **Output**: `any`, same type as input
 * 
 * ##### Options
 * 
 * * `batch_count` (`integer`, _defaults to_ `10`): Accumulated batch size
 * 
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class IterableBatchTask extends AbstractConfigurableTask implements FlushableTaskInterface, IterableTaskInterface
{

    /** @var \SplQueue */
    protected $outputQueue;

    /** @var bool */
    protected $flushMode = false;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function initialize(ProcessState $state)
    {
        parent::initialize($state);
        $this->outputQueue = new \SplQueue();
    }


    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function flush(ProcessState $state)
    {
        $this->flushMode = true;
        if ($this->outputQueue->isEmpty()) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->outputQueue->dequeue());
        }
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $batchCount = $this->getOption($state, 'batch_count');

        // Register new input
        if (!$this->flushMode) {
            $this->outputQueue->enqueue($this->processInput($state));
        }

        // Detect flushing
        if (null !== $batchCount && \count($this->outputQueue) >= $batchCount) {
            $this->flushMode = true;
        }

        // Flush or skip
        if ($this->flushMode) {
            $state->setOutput($this->outputQueue->dequeue());
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function next(ProcessState $state)
    {
        // Stop flushing once over
        if (!\count($this->outputQueue)) {
            $this->flushMode = false;
        }

        return $this->flushMode;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'batch_count' => 10,
            ]
        );

        $resolver->setAllowedTypes('batch_count', 'integer');
    }

    /**
     * Override this method to add a custom processing behavior
     *
     * @param ProcessState $state
     *
     * @return mixed
     */
    protected function processInput(ProcessState $state)
    {
        return $state->getInput();
    }
}
