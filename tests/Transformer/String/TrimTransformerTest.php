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

use CleverAge\ProcessBundle\Transformer\String\TrimTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(TrimTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(TrimTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(TrimTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\CoversMethod(TrimTransformer::class, 'configureOptions')]
class TrimTransformerTest extends TestCase
{
    public function testTransformTrimsStringWithDefaultCharlist(): void
    {
        $transformer = new TrimTransformer();
        $value = '  trim me  ';

        $result = $transformer->transform($value);

        $this->assertEquals('trim me', $result);
    }

    public function testTransformTrimsStringWithCustomCharlist(): void
    {
        $transformer = new TrimTransformer();
        $value = '-trim me-';
        $options = ['charlist' => '-'];

        $result = $transformer->transform($value, $options);

        $this->assertEquals('trim me', $result);
    }

    public function testTransformReturnsNullForNullValue(): void
    {
        $transformer = new TrimTransformer();
        $value = null;

        $result = $transformer->transform($value);

        $this->assertNull($result);
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new TrimTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('trim', $code);
    }

    public function testConfigureOptionsSetsDefaultOptions(): void
    {
        $resolver = new OptionsResolver();
        $transformer = new TrimTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['charlist' => " \t\n\r\0\x0B"], $resolvedOptions);
    }

    /**
     * trim() removes bytes: a multibyte character of the list cut the characters sharing its bytes ("Été" gave "\x89t").
     */
    public function testTransformWithAMultibyteCharlist(): void
    {
        $transformer = new TrimTransformer();

        $this->assertSame('Ét', $transformer->transform('Été', ['charlist' => 'é']));
        $this->assertSame('t', $transformer->transform('ÉtéÉ', ['charlist' => 'éÉ']));
        $this->assertSame('t', $transformer->transform('« t »', ['charlist' => '« »']));
        // A single-byte charlist keeps the ranges of trim()
        $this->assertSame('123', $transformer->transform('abc123cba', ['charlist' => 'a..c']));
    }

    /**
     * mb_trim() does not support the ranges: "a..zé" removed "a", "." and "z" only, and kept "b" to "y".
     */
    public function testRangesWithAMultibyteCharlist(): void
    {
        $transformer = new TrimTransformer();

        $this->assertSame('-word-', $transformer->transform('bcé-word-zé', ['charlist' => 'a..zé']));
        $this->assertSame('.1.', $transformer->transform('é.1.é', ['charlist' => 'é0..0']));
        // An invalid range (from a greater character) is not expanded, as with trim()
        $this->assertSame('b', $transformer->transform('z.b.a', ['charlist' => 'z..aé']));
    }
}
