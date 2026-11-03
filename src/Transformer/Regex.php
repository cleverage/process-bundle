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

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Regular expressions of the options: an invalid pattern is rejected with the configuration, and a PCRE error (e.g.
 * invalid UTF-8 with the "u" modifier, backtracking limit) is not taken for "no match".
 *
 * @internal
 */
final class Regex
{
    /**
     * @throws InvalidOptionsException if the pattern cannot be compiled
     */
    public static function validate(string $pattern, string $option = 'pattern'): string
    {
        $error = null;
        set_error_handler(static function (int $type, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });
        try {
            $valid = false !== preg_match($pattern, '');
        } finally {
            restore_error_handler();
        }
        if (!$valid) {
            $message = null !== $error ? preg_replace('/^preg_match\(\): /', '', $error) : preg_last_error_msg();

            throw new InvalidOptionsException(\sprintf('Invalid regular expression "%s" in the "%s" option: %s', $pattern, $option, $message));
        }

        return $pattern;
    }

    /**
     * @throws UnexpectedValueException if the last PCRE function has failed
     */
    public static function checkLastError(string $pattern): void
    {
        if (\PREG_NO_ERROR !== preg_last_error()) {
            throw new UnexpectedValueException(\sprintf('The regular expression "%s" has failed: %s', $pattern, preg_last_error_msg()));
        }
    }
}
