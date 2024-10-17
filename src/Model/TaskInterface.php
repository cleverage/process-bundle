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
 * Must be implemented by tasks services
 * The service can read the input value from ProcessState and write it's output to it.
 *
 * @see    ProcessState for more informations about available actions
 */
interface TaskInterface
{
    public function execute(ProcessState $state): void;
}
