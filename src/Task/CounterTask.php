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
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Count the number of times the task is processed and continue every N iteration (skip the rest of the time)
 * Flush at the end with the actual count
 */
class CounterTask extends AbstractConfigurableTask implements FlushableTaskInterface
{
    /**
     * @var int
     */
    protected $counter = 0;

    public function execute(ProcessState $state): void
    {
        $this->counter++;
        $modulo = $this->getOption($state, 'flush_every');
        if ($this->counter % $modulo === 0) {
            $state->setOutput($this->counter);
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * Condition is inversed during flush
     */
    public function flush(ProcessState $state): void
    {
        $modulo = $this->getOption($state, 'flush_every');
        if ($this->counter % $modulo === 0) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->counter);
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['flush_every']);
        $resolver->setAllowedTypes('flush_every', ['int']);
    }
}
