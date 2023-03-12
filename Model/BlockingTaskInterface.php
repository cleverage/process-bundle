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
 * Allow the task to block the flow of the process and proceed with child tasks only when all iterations are over.
 */
interface BlockingTaskInterface extends TaskInterface
{
    public function proceed(ProcessState $state): void;
}
