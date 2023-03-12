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

namespace CleverAge\ProcessBundle\Model;

/**
 * Allow the task to be iterated over until "next" returns false
 */
interface IterableTaskInterface extends TaskInterface
{
    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     *
     * @return bool
     */
    public function next(ProcessState $state);
}
