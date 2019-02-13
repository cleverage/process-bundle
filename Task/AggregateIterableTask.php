<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Class AggregateIterableTask
 *
 * Aggregate the result of iterable tasks in an array
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class AggregateIterableTask implements BlockingTaskInterface
{
    /** @var array */
    protected $result = [];

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $this->result[] = $state->getInput();
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        if (0 === \count($this->result)) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->result);
        }
    }
}
