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

namespace CleverAge\ProcessBundle\Tests\Transformer\Object;

use CleverAge\ProcessBundle\Transformer\Object\PropertyAccessorTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(PropertyAccessorTransformer::class)]
class PropertyAccessorTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('property_accessor', $this->createTransformer()->getCode());
    }

    /**
     * @return iterable<string, array{mixed, string, mixed}>
     */
    public static function readProvider(): iterable
    {
        yield 'array key' => [['key' => 'foo'], '[key]', 'foo'];
        yield 'numeric array key' => [['a', 'b', 'c'], '[2]', 'c'];
        yield 'nested array keys' => [['a' => ['b' => 'foo']], '[a][b]', 'foo'];
        yield 'object property' => [(object) ['name' => 'foo'], 'name', 'foo'];
        yield 'nested object property' => [(object) ['a' => (object) ['b' => 'foo']], 'a.b', 'foo'];
        yield 'mixed object and array' => [(object) ['a' => ['b' => 'foo']], 'a[b]', 'foo'];
    }

    #[DataProvider('readProvider')]
    public function testTransform(mixed $value, string $propertyPath, mixed $expected): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property_path' => $propertyPath]);

        self::assertSame($expected, $transformer->transform($value, $options));
    }

    public function testNullInputThrowsByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property_path' => 'name']);

        $this->expectException(\TypeError::class);

        $transformer->transform(null, $options);
    }

    public function testNullInputReturnsNullWithIgnoreNull(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property_path' => 'name', 'ignore_null' => true]);

        self::assertNull($transformer->transform(null, $options));
    }

    public function testMissingPropertyThrowsByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property_path' => 'missing']);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform(new \stdClass(), $options);
    }

    public function testMissingPropertyReturnsNullWithIgnoreMissing(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property_path' => 'missing', 'ignore_missing' => true]);

        self::assertNull($transformer->transform(new \stdClass(), $options));
    }

    public function testMissingIndexThrowsWithAStrictAccessor(): void
    {
        $transformer = $this->createTransformer(
            PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor()
        );
        $options = $this->resolveOptions($transformer, ['property_path' => '[address][city]']);

        $this->expectException(NoSuchIndexException::class);

        $transformer->transform(['address' => []], $options);
    }

    public function testMissingIndexReturnsNullWithIgnoreMissing(): void
    {
        $transformer = $this->createTransformer(
            PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor()
        );
        $options = $this->resolveOptions($transformer, ['property_path' => '[address][city]', 'ignore_missing' => true]);

        self::assertNull($transformer->transform(['address' => []], $options));
    }

    public function testMissingIndexReturnsNullWithTheDefaultAccessor(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property_path' => '[missing]']);

        self::assertNull($transformer->transform([], $options));
    }

    public function testPropertyPathIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions($this->createTransformer(), []);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'property_path not a string' => [['property_path' => 1]];
        yield 'ignore_null not a bool' => [['property_path' => 'a', 'ignore_null' => 'yes']];
        yield 'ignore_missing not a bool' => [['property_path' => 'a', 'ignore_missing' => 1]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsAreRejected(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($this->createTransformer(), $options);
    }

    private function createTransformer(?PropertyAccessorInterface $accessor = null): PropertyAccessorTransformer
    {
        return new PropertyAccessorTransformer($accessor ?? PropertyAccess::createPropertyAccessor());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(PropertyAccessorTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
