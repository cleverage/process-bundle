<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;

/**
 * Thrown when the process configuration cannot be resolved
 */
class InvalidProcessConfigurationException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param TaskConfiguration $taskConfig
     * @param array             $mainTaskList
     *
     * @return InvalidProcessConfigurationException
     */
    public static function createNotInMain(
        TaskConfiguration $taskConfig,
        array $mainTaskList
    ): InvalidProcessConfigurationException {
        $taskListStr = '['.implode(', ', $mainTaskList).']';

        return new self("Task '{$taskConfig->getCode()}' is not in main task list : {$taskListStr}");
    }
}
