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

use CleverAge\ProcessBundle\Transformer\ConvertValueTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ConvertValueTransformer::class)]
class ConvertValueTransformerTest extends TestCase
{
    private const MAP = [
        'TEXTE' => 'text',
        'NUMERIQUE' => 'number',
        1 => 'one',
        '1.5' => 'one and a half',
        '' => 'empty',
        'null_value' => null,
    ];

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public static function mappedValueProvider(): iterable
    {
        yield 'string key' => ['TEXTE', 'text'];
        yield 'int key' => [1, 'one'];
        yield 'numeric string matches int key' => ['1', 'one'];
        yield 'empty string key' => ['', 'empty'];
        yield 'null mapped value' => ['null_value', null];
    }

    #[DataProvider('mappedValueProvider')]
    public function testMappedValueIsConverted(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, $this->transform($value, []));
    }

    public function testNullAlwaysReturnsNull(): void
    {
        self::assertNull($this->transform(null, []));
        self::assertNull($this->transform(null, ['map' => [], 'auto_cast' => true]));
    }

    public function testMissingValueThrowsByDefault(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Missing value in map 'DATE'");

        $this->transform('DATE', []);
    }

    public function testMissingValueReturnsNullWithIgnoreMissing(): void
    {
        self::assertNull($this->transform('DATE', ['ignore_missing' => true]));
    }

    public function testMissingValueIsKeptWithKeepMissing(): void
    {
        self::assertSame('DATE', $this->transform('DATE', ['keep_missing' => true]));
        self::assertSame(42, $this->transform(42, ['keep_missing' => true]));
    }

    public function testKeepMissingTakesPrecedenceOverIgnoreMissing(): void
    {
        self::assertSame('DATE', $this->transform('DATE', ['keep_missing' => true, 'ignore_missing' => true]));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidKeyProvider(): iterable
    {
        yield 'float' => [1.5, 'double'];
        yield 'bool' => [true, 'boolean'];
        yield 'object' => [new \stdClass(), 'object'];
        yield 'array' => [['TEXTE'], 'array'];
    }

    #[DataProvider('invalidKeyProvider')]
    public function testInvalidKeyThrowsWithoutAutoCast(mixed $value, string $type): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Value of type {$type} is not a valid array index, set auto_cast to true to cast it to a string");

        $this->transform($value, []);
    }

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public static function autoCastProvider(): iterable
    {
        yield 'float' => [1.5, 'one and a half'];
        yield 'true' => [true, 'one'];
        yield 'false' => [false, 'empty'];
        yield 'stringable' => [new class implements \Stringable {
            public function __toString(): string
            {
                return 'NUMERIQUE';
            }
        }, 'number'];
    }

    #[DataProvider('autoCastProvider')]
    public function testAutoCastConvertsInvalidKeyToString(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, $this->transform($value, ['auto_cast' => true]));
    }

    public function testAutoCastKeepsCastValueWhenMissing(): void
    {
        self::assertSame('2.5', $this->transform(2.5, ['auto_cast' => true, 'keep_missing' => true]));
    }

    public function testArrayIsRejectedEvenWithAutoCast(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Unexpected input of type 'array' in convert_value transformer");

        $this->transform(['TEXTE'], ['auto_cast' => true]);
    }

    public function testMapIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions([]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'map not an array' => [['map' => 'foo']];
        yield 'ignore_missing not a bool' => [['map' => [], 'ignore_missing' => 1]];
        yield 'keep_missing not a bool' => [['map' => [], 'keep_missing' => 'yes']];
        yield 'auto_cast not a bool' => [['map' => [], 'auto_cast' => null]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrow(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($options);
    }

    public function testGetCode(): void
    {
        self::assertSame('convert_value', (new ConvertValueTransformer())->getCode());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function transform(mixed $value, array $options): mixed
    {
        return (new ConvertValueTransformer())->transform($value, $this->resolveOptions($options + ['map' => self::MAP]));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        (new ConvertValueTransformer())->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
