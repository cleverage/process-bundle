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
 * Thrown by the bundle when an error that can only be found at runtime (e.g. a file that cannot be read).
 */
class RuntimeException extends \RuntimeException implements ProcessExceptionInterface
{
}
