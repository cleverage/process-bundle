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
 * Thrown when an exclusive process (exclusive: true) is executed while another execution of it holds its lock.
 */
class ProcessAlreadyRunningException extends \RuntimeException implements ProcessExceptionInterface
{
    public static function create(string $processCode): self
    {
        return new self("Process {$processCode} is exclusive and is already running: it is not executed.");
    }
}
