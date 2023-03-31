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

namespace CleverAge\ProcessBundle\Exception;

use UnexpectedValueException;

/**
 * Exception thrown when trying to fetch a missing transformer
 */
class MissingTransformerException extends UnexpectedValueException implements ProcessExceptionInterface
{
    public static function create(?string $code = ''): self
    {
        $errorStr = "No transformer with code : $code";

        return new self($errorStr);
    }
}
