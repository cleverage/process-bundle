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

namespace CleverAge\ProcessBundle\Exception;

/**
 * Set on the task state when a process is stopped by a ProcessStopCheckerInterface or by the StopTask: the process fails
 * with a ProcessFailedException, whose previous exception is this one.
 */
class ProcessStoppedException extends \RuntimeException implements ProcessExceptionInterface
{
    public static function create(string $processCode, string $taskCode): self
    {
        return new self("Process {$processCode} has been stopped before task {$taskCode}.");
    }

    public static function createByTask(string $processCode, string $taskCode): self
    {
        return new self("Process {$processCode} has been stopped by task {$taskCode}.");
    }
}
