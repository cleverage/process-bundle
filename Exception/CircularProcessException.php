<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Thrown when a circular dependency is found in a process
 */
class CircularProcessException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param string $processCode
     * @param string $taskCode
     *
     * @return CircularProcessException
     */
    public static function create($processCode, $taskCode)
    {
        $errorStr = "Process '{$processCode}' contains circular dependency (task '{$taskCode}' has itself as ancestor, at some point)";

        return new self($errorStr);
    }
}
