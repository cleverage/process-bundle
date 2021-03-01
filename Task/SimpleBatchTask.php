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
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Group elements by batch of a defined size.
 *
 * Simple example of how to manage an internal buffer for batch processing.
 *
 * ##### Task reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Task\SimpleBatchTask`
 * * **Flushable task**
 * * **Input**: `any`
 * * **Output**: `array`, containing received inputs since previous flush
 *
 * ##### Options
 *
 * * `batch_count` (`int` _defaults to_ `10`): description
 *
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class SimpleBatchTask extends AbstractConfigurableTask implements FlushableTaskInterface
{
    /** @var array */
    protected $elements = [];

    /**
     * {@inheritDoc}
     * @internal
     */
    public function flush(ProcessState $state)
    {
        if (0 === \count($this->elements)) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->elements);
            $this->elements = [];
        }
    }

    /**
     * {@inheritDoc}
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $batchCount = $this->getOption($state, 'batch_count');
        $this->elements[] = $state->getInput();

        if (null !== $batchCount && \count($this->elements) >= $batchCount) {
            $state->setOutput($this->elements);
            $this->elements = [];
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * {@inheritDoc}
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'batch_count' => 10,
            ]
        );
    }
}
