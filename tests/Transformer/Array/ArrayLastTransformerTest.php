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

namespace CleverAge\ProcessBundle\Tests\Transformer\Array;

use CleverAge\ProcessBundle\Transformer\Array\ArrayLastTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayLastTransformer::class)]
class ArrayLastTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('array_last', (new ArrayLastTransformer())->getCode());
    }

    /**
     * @return iterable<string, array{array<mixed>, mixed}>
     */
    public static function lastElementProvider(): iterable
    {
        yield 'list' => [['foo', 'bar', 'baz'], 'baz'];
        yield 'single element' => [['foo'], 'foo'];
        yield 'associative array' => [['a' => 1, 'b' => 2], 2];
        yield 'numeric keys not in order' => [[5 => 'x', 2 => 'y'], 'y'];
        yield 'null last element' => [['foo', null], null];
        yield 'array last element' => [['foo', ['bar']], ['bar']];
    }

    /**
     * @param array<mixed> $value
     */
    #[DataProvider('lastElementProvider')]
    public function testTransform(array $value, mixed $expected): void
    {
        self::assertSame($expected, (new ArrayLastTransformer())->transform($value));
    }

    public function testNonArrayInputThrows(): void
    {
        $this->expectException(\TypeError::class);

        // @phpstan-ignore method.resultUnused (the call is expected to throw)
        (new ArrayLastTransformer())->transform('foo');
    }
}
