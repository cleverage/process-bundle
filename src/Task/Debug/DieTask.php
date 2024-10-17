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

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class DieTask.
 *
 * Stops the process brutally
 *
 * @example https://github.com/cleverage/process-bundle-ui-demo/blob/main/config/packages/process/demo.die.yaml
 */
class DieTask implements TaskInterface
{
    public function execute(ProcessState $state): never
    {
        var_dump(Helper::formatMemory(memory_get_peak_usage(true)));
        exit;
    }
}
