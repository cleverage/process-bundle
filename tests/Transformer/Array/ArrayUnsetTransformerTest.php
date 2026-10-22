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

use CleverAge\ProcessBundle\Transformer\Array\ArrayUnsetTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayUnsetTransformer::class)]
class ArrayUnsetTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('array_unset', (new ArrayUnsetTransformer())->getCode());
    }

    /**
     * @return iterable<string, array{array<mixed>, string|int, array<mixed>}>
     */
    public static function unsetProvider(): iterable
    {
        yield 'string key' => [['id' => 1, 'temporary_field' => 'foo'], 'temporary_field', ['id' => 1]];
        yield 'int key' => [['a', 'b', 'c'], 1, [0 => 'a', 2 => 'c']];
        yield 'numeric string key' => [['a', 'b'], '0', [1 => 'b']];
        yield 'missing key' => [['id' => 1], 'missing', ['id' => 1]];
        yield 'null value' => [['id' => 1, 'foo' => null], 'foo', ['id' => 1]];
    }

    /**
     * @param array<mixed> $value
     * @param array<mixed> $expected
     */
    #[DataProvider('unsetProvider')]
    public function testTransform(array $value, string|int $key, array $expected): void
    {
        $transformer = new ArrayUnsetTransformer();

        self::assertSame($expected, $transformer->transform($value, $this->resolveOptions($transformer, ['key' => $key])));
    }

    public function testNonArrayInputThrows(): void
    {
        $transformer = new ArrayUnsetTransformer();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Given value is not an array');

        $transformer->transform(new \ArrayObject(['id' => 1]), $this->resolveOptions($transformer, ['key' => 'id']));
    }

    public function testKeyIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions(new ArrayUnsetTransformer(), []);
    }

    public function testKeyMustBeAStringOrAnInt(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new ArrayUnsetTransformer(), ['key' => 1.5]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(ArrayUnsetTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
