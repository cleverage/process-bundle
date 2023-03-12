<?php declare(strict_types=1);
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
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Simple example of how to manage an internal buffer for batch processing
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class SimpleBatchTask extends AbstractConfigurableTask implements FlushableTaskInterface
{
    /** @var array */
    protected $elements = [];

    /**
     * @param ProcessState $state
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
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws \InvalidArgumentException
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
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
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
