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

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\Regex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[CoversClass(Regex::class)]
class RegexTest extends TestCase
{
    public function testValidPattern(): void
    {
        self::assertSame('/^a+$/u', Regex::validate('/^a+$/u'));
    }

    public function testInvalidPattern(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Invalid regular expression "a" in the "match" option: Delimiter must not be alphanumeric, backslash, or NUL byte');

        Regex::validate('a', 'match');
    }

    /**
     * The error handler of the application (e.g. the Symfony one in debug mode) is restored.
     */
    public function testTheErrorHandlerIsRestored(): void
    {
        $handler = static fn (): bool => true;
        set_error_handler($handler);
        try {
            try {
                Regex::validate('/(a/');
            } catch (InvalidOptionsException) {
            }
            self::assertSame($handler, set_error_handler(null));
        } finally {
            restore_error_handler();
            restore_error_handler();
        }
    }

    public function testCheckLastError(): void
    {
        preg_match('/a/u', 'a');
        Regex::checkLastError('/a/u');

        preg_match('/a/u', "a\xff");
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The regular expression "/a/u" has failed: Malformed UTF-8 characters, possibly incorrectly encoded');

        Regex::checkLastError('/a/u');
    }
}
