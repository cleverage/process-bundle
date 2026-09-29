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
}
