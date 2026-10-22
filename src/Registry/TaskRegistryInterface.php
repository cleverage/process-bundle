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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Gives the task service of a task configuration (implemented by TaskRegistry, autowired by this interface).
 */
interface TaskRegistryInterface
{
    /**
     * @throws \UnexpectedValueException when the service cannot be resolved or is not a TaskInterface
     */
    public function getTask(TaskConfiguration $taskConfiguration): TaskInterface;
}
