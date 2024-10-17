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
 */
interface FlushableTaskInterface extends TaskInterface
{
    public function flush(ProcessState $state): void;
}
