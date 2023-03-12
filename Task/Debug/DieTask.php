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

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Class DieTask
 *
 * Stops the process brutally
 */
class DieTask implements TaskInterface
{
    public function execute(ProcessState $state): never
    {
        die();
    }
}
