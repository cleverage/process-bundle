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

use CleverAge\ProcessBundle\Transformer\Array\ArrayFirstTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayFirstTransformer::class)]
class ArrayFirstTransformerTest extends TestCase
{
    /**
     * @return iterable<string, array{iterable<mixed>, mixed}>
     */
    public static function iterableProvider(): iterable
    {
        yield 'list' => [[1, 2, 3], 1];
        yield 'associative array' => [['a' => 'foo', 'b' => 'bar'], 'foo'];
        yield 'empty array' => [[], false];
        yield 'iterator' => [new \ArrayIterator(['foo', 'bar']), 'foo'];
        yield 'generator' => [(static function (): \Generator {
            yield 'foo';
            yield 'bar';
        })(), 'foo'];
        yield 'empty iterator' => [new \ArrayIterator([]), false];
    }

    /**
     * @param iterable<mixed> $value
     */
    #[DataProvider('iterableProvider')]
    public function testTransformReturnsTheFirstElement(iterable $value, mixed $expected): void
    {
        $transformer = new ArrayFirstTransformer();

        self::assertSame($expected, $transformer->transform($value, $this->resolveOptions($transformer)));
    }

    public function testAllowNotIterableDoesNotChangeIterableValues(): void
    {
        $transformer = new ArrayFirstTransformer();
        $options = $this->resolveOptions($transformer, ['allow_not_iterable' => true]);

        self::assertSame(1, $transformer->transform([1, 2, 3], $options));
        self::assertSame('foo', $transformer->transform(new \ArrayIterator(['foo', 'bar']), $options));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function notIterableProvider(): iterable
    {
        yield 'string' => ['not_iterable_value'];
        yield 'int' => [42];
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('notIterableProvider')]
    public function testNotIterableValueThrowsByDefault(mixed $value): void
    {
        $transformer = new ArrayFirstTransformer();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Given value is not iterable');

        $transformer->transform($value, $this->resolveOptions($transformer));
    }

    #[DataProvider('notIterableProvider')]
    public function testNotIterableValueIsReturnedUnchangedWhenAllowed(mixed $value): void
    {
        $transformer = new ArrayFirstTransformer();

        self::assertSame($value, $transformer->transform($value, $this->resolveOptions($transformer, ['allow_not_iterable' => true])));
    }

    public function testAllowNotIterableMustBeABoolean(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new ArrayFirstTransformer(), ['allow_not_iterable' => 'yes']);
    }

    public function testGetCode(): void
    {
        self::assertSame('array_first', (new ArrayFirstTransformer())->getCode());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(ArrayFirstTransformer $transformer, array $options = []): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
