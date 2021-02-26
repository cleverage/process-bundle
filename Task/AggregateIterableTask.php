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

use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * Aggregate the result of iterable tasks in an array
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\AggregateIterableTask`
 *  * **Blocking task**
 *  * **Input**: `any`
 *  * **Output**: `array`, of received inputs
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class AggregateIterableTask implements BlockingTaskInterface
{
    /** @var array */
    protected $result = [];

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $this->result[] = $state->getInput();
    }

    /**
     * {@inheritDoc}
     *
     * @internal
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
