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

namespace CleverAge\ProcessBundle\Tests;

/**
 * For the tests using a deprecated feature whose deprecation is checked by another test: the tests fail on
 * deprecations (failOnDeprecation).
 */
trait IgnoreDeprecationsTrait
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    private static function ignoreDeprecations(callable $callback): mixed
    {
        set_error_handler(static fn (): bool => true, \E_USER_DEPRECATED);
        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
