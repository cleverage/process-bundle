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
 * Thrown by the bundle when a value does not match the expected type or set of values (e.g. an invalid input of a task).
 */
class UnexpectedValueException extends \UnexpectedValueException implements ProcessExceptionInterface
{
}
