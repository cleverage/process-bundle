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

namespace CleverAge\ProcessBundle\Tests\Exception;

use CleverAge\ProcessBundle\Exception\InvalidArgumentException;
use CleverAge\ProcessBundle\Exception\LogicException;
use CleverAge\ProcessBundle\Exception\ProcessExceptionInterface;
use CleverAge\ProcessBundle\Exception\RuntimeException;
use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\String\ImplodeTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The exceptions thrown by the bundle can be caught as ProcessExceptionInterface, and still as the SPL exceptions they
 * were before.
 */
#[CoversClass(InvalidArgumentException::class)]
#[CoversClass(LogicException::class)]
#[CoversClass(RuntimeException::class)]
#[CoversClass(UnexpectedValueException::class)]
#[UsesClass(ImplodeTransformer::class)]
class SplExceptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<\Throwable>, class-string<\Throwable>}>
     */
    public static function provideExceptions(): iterable
    {
        yield 'UnexpectedValueException' => [UnexpectedValueException::class, \UnexpectedValueException::class];
        yield 'RuntimeException' => [RuntimeException::class, \RuntimeException::class];
        yield 'InvalidArgumentException' => [InvalidArgumentException::class, \InvalidArgumentException::class];
        yield 'LogicException' => [LogicException::class, \LogicException::class];
    }

    /**
     * @param class-string<\Throwable> $class
     * @param class-string<\Throwable> $splClass
     */
    #[DataProvider('provideExceptions')]
    public function testExtendsTheSplExceptionAndImplementsTheInterface(string $class, string $splClass): void
    {
        $exception = new $class('message', 12);

        $this->assertInstanceOf($splClass, $exception);
        $this->assertInstanceOf(ProcessExceptionInterface::class, $exception);
        $this->assertSame(['message', 12], [$exception->getMessage(), $exception->getCode()]);
    }

    public function testBundleExceptionsCanBeCaughtByTheInterface(): void
    {
        try {
            (new ImplodeTransformer())->transform('not an array', ['separator' => ',']);
            $this->fail('No exception thrown');
        } catch (ProcessExceptionInterface $exception) {
            $this->assertInstanceOf(\UnexpectedValueException::class, $exception);
        }
    }
}
