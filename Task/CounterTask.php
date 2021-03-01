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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Outputs periodically the number of time this task is called.
 *
 * Count the number of times the task is processed and continue every N iteration (skip the rest of the time)
 * Flush at the end with the actual count.
 *
 * It can be useful for batch like workflow.
 * See also {@see IterableBatchTask}.
 *
 * ##### Task reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Task\CounterTask`
 * * **Flushable task**
 * * **Input**: _ignored_
 * * **Output**: `int`, the number of time this task is called
 *
 * ##### Options
 *
 * * `flush_every` (`int`, _required_): the period at which the task will produce outputs
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CounterTask extends AbstractConfigurableTask implements FlushableTaskInterface
{
    /** @var int */
    protected $counter = 0;

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state): void
    {
        $this->counter++;
        $modulo = $this->getOption($state, 'flush_every');
        if (0 === $this->counter % $modulo) {
            $state->setOutput($this->counter);
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * Condition is inversed during flush
     *
     * @param ProcessState $state
     */
    public function flush(ProcessState $state): void
    {
        $modulo = $this->getOption($state, 'flush_every');
        if (0 === $this->counter % $modulo) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->counter);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'flush_every',
            ]
        );
        $resolver->setAllowedTypes('flush_every', ['int']);
    }
}
