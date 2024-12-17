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

use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Class AggregateIterableTask.
 *
 * Aggregate the result of iterable tasks in an array
 */
class AggregateIterableTask implements BlockingTaskInterface
{
    protected array $result = [];

    public function execute(ProcessState $state): void
    {
        $this->result[] = $state->getInput();
    }

    public function proceed(ProcessState $state): void
    {
        if ([] === $this->result) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->result);
        }
    }
}
