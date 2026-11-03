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

namespace CleverAge\ProcessBundle\Tests\Transformer\String;

use CleverAge\ProcessBundle\Transformer\String\SlugifyTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(SlugifyTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(SlugifyTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(SlugifyTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(SlugifyTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\UsesClass(\CleverAge\ProcessBundle\Transformer\Regex::class)]
class SlugifyTransformerTest extends TestCase
{
    public function testTransformWithDefaultOptions(): void
    {
        $transformer = new SlugifyTransformer();

        $this->assertSame('helene_dupont', $transformer->transform(' Hélène  <b>Dupont</b>! ', $this->resolveOptions($transformer)));
    }

    public function testTransformWithCustomTransliteratorAndSeparator(): void
    {
        $transformer = new SlugifyTransformer();
        $options = $this->resolveOptions($transformer, [
            'transliterator' => 'Any-Latin; Latin-ASCII',
            'separator' => '-',
        ]);

        $this->assertSame('privet-mir', $transformer->transform('Привет мир', $options));
    }

    public function testConfigureOptionsRejectsInvalidTransliterator(): void
    {
        $transformer = new SlugifyTransformer();

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Invalid "transliterator" option');

        $this->resolveOptions($transformer, ['transliterator' => 'Not-A-Real-Transliterator']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidOptionTypes(): iterable
    {
        yield 'transliterator' => [['transliterator' => 123]];
        yield 'replace' => [['replace' => ['/a/']]];
        yield 'separator' => [['separator' => []]];
        yield 'strip_tags' => [['strip_tags' => 'yes']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidOptionTypes')]
    public function testConfigureOptionsRejectsInvalidOptionTypes(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new SlugifyTransformer(), $options);
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $this->assertSame('slugify', (new SlugifyTransformer())->getCode());
    }

    public function testValueThatCannotBeTransliteratedThrows(): void
    {
        $transformer = new SlugifyTransformer();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to transliterate the value');

        $transformer->transform("a\xff", $this->resolveOptions($transformer));
    }

    /**
     * The default transliterator only removes the accents: the letters that are not decomposed are removed with the
     * replace pattern. Any-Latin; Latin-ASCII keeps them.
     */
    public function testLettersWithoutDecomposition(): void
    {
        $transformer = new SlugifyTransformer();

        $this->assertSame('c_ur_stra_e_odz', $transformer->transform('Cœur Straße Łódź', $this->resolveOptions($transformer)));
        $this->assertSame('coeur_strasse_lodz', $transformer->transform('Cœur Straße Łódź', $this->resolveOptions($transformer, [
            'transliterator' => 'Any-Latin; Latin-ASCII',
        ])));
    }

    /**
     * strip_tags() removes everything after a "<".
     */
    public function testStripTags(): void
    {
        $transformer = new SlugifyTransformer();

        $this->assertSame('tom', $transformer->transform('Tom<Jerry', $this->resolveOptions($transformer)));
        $this->assertSame('tom_jerry', $transformer->transform('Tom<Jerry', $this->resolveOptions($transformer, ['strip_tags' => false])));
        $this->assertSame('3_love', $transformer->transform('<3 love', $this->resolveOptions($transformer, ['strip_tags' => false])));
    }

    /**
     * The characters left by the transliterator are lowercased too (strtolower() only lowercased ASCII letters).
     */
    public function testMultibyteLowercase(): void
    {
        $transformer = new SlugifyTransformer();
        $options = $this->resolveOptions($transformer, ['transliterator' => 'NFC', 'replace' => '/[^\p{L}0-9]+/u']);

        $this->assertSame('élan_ça', $transformer->transform('ÉLAN ÇA', $options));
    }

    public function testScalarAndStringableValues(): void
    {
        $transformer = new SlugifyTransformer();
        $options = $this->resolveOptions($transformer);
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Hélène';
            }
        };

        $this->assertSame('123', $transformer->transform(123, $options));
        $this->assertSame('1_5', $transformer->transform(1.5, $options));
        $this->assertSame('helene', $transformer->transform($stringable, $options));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'null' => [null];
        yield 'array' => [['a']];
        yield 'object' => [new \stdClass()];
    }

    /**
     * A raw TypeError was thrown.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidValues')]
    public function testInvalidValues(mixed $value): void
    {
        $transformer = new SlugifyTransformer();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The slugify transformer expects a string, '.get_debug_type($value).' given.');

        $transformer->transform($value, $this->resolveOptions($transformer));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(SlugifyTransformer $transformer, array $options = []): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }

    public function testInvalidReplaceIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('in the "replace" option');

        $this->resolveOptions(new SlugifyTransformer(), ['replace' => '/[a-z/']);
    }
}
