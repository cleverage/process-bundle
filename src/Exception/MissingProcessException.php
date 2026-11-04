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

use CleverAge\ProcessBundle\Configuration\DidYouMean;

/**
 * Exception thrown when trying to fetch a missing process.
 */
class MissingProcessException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param iterable<string> $knownCodes to suggest the closest one
     */
    public static function create(?string $code = '', iterable $knownCodes = []): self
    {
        $errorStr = "No process with code : {$code}".DidYouMean::suggest((string) $code, $knownCodes);

        return new self($errorStr);
    }
}
