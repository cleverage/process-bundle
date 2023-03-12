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

use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Class AggregateIterableTask
 *
 * Aggregate the result of iterable tasks in an array
 */
class AggregateIterableTask implements BlockingTaskInterface
{
    /**
     * @var array
     */
    protected $result = [];

    public function execute(ProcessState $state): void
    {
        $this->result[] = $state->getInput();
    }

    public function proceed(ProcessState $state): void
    {
        if (\count($this->result) === 0) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->result);
        }
    }
}
