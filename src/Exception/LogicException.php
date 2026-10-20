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
 * Thrown by the bundle when a wrong usage of the bundle (e.g. an invalid configuration), that should be fixed in the code.
 */
class LogicException extends \LogicException implements ProcessExceptionInterface
{
}
