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

namespace CleverAge\ProcessBundle\Model;

/**
 * When iterations are over, this allows task that have some inner buffer to flush it to the output.
 *
 * flush() may be called several times on the same task during a process (once for each resolved ancestor, and each time
 * an upstream iterable task finishes its iterations): implementations must be idempotent, and skip the state
 * (ProcessState::setSkipped(true)) when there is nothing new to output.
 */
interface FlushableTaskInterface extends TaskInterface
{
    public function flush(ProcessState $state): void;
}
