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
 * Thrown when a process is stopped by a task error (error strategy "stop").
 *
 * The original exception is available with getPrevious().
 */
class ProcessFailedException extends \RuntimeException implements ProcessExceptionInterface
{
    public static function create(string $processCode, string $taskCode, \Throwable $previous): self
    {
        $errorStr = "Process {$processCode} has failed during process {$taskCode}";
        $errorStr .= " with message: '{$previous->getMessage()}'.\n";

        return new self($errorStr, 0, $previous);
    }
}
