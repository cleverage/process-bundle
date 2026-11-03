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

use CleverAge\ProcessBundle\Transformer\MultiReplaceTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(MultiReplaceTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(MultiReplaceTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(MultiReplaceTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(MultiReplaceTransformer::class, 'getCode')]
class MultiReplaceTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new MultiReplaceTransformer();
        $value = 'This is a test string.';
        $options = [
            'replace_mapping' => [
                'This' => 'That',
                'string' => 'sentence',
            ],
        ];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('That is a test sentence.', $transformedValue);
    }

    public function testTransformWithEmptyReplaceMapping(): void
    {
        $transformer = new MultiReplaceTransformer();
        $value = 'This is a test string.';
        $options = [
            'replace_mapping' => [],
        ];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('This is a test string.', $transformedValue);
    }

    public function testTransformWithNullValue(): void
    {
        $transformer = new MultiReplaceTransformer();
        $value = null;
        $options = [
            'replace_mapping' => [
                'This' => 'That',
                'string' => 'sentence',
            ],
        ];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('', $transformedValue);
    }

    public function testConfigureOptions(): void
    {
        $transformer = new MultiReplaceTransformer();
        $resolver = new OptionsResolver();
        $resolver->setDefault('replace_mapping', []);

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('replace_mapping'));

        $resolvedOptions = $resolver->resolve();
        $this->assertEquals(['replace_mapping'], array_keys($resolvedOptions));
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new MultiReplaceTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('multi_replace', $code);
    }

    /**
     * A numeric key of the mapping is an int: str_replace() threw a TypeError.
     */
    public function testTransformWithNumericKeys(): void
    {
        $transformer = new MultiReplaceTransformer();

        $this->assertSame('one ten', $transformer->transform('1 10', ['replace_mapping' => ['10' => 'ten', '1' => 'one', 2 => 3]]));
    }

    public function testAReplacementMustBeAString(): void
    {
        $resolver = new OptionsResolver();
        (new MultiReplaceTransformer())->configureOptions($resolver);

        $this->assertSame(['a' => 1, 'b' => null], $resolver->resolve(['replace_mapping' => ['a' => 1, 'b' => null]])['replace_mapping']);
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The replacements of the "replace_mapping" option must be strings, array given.');
        $resolver->resolve(['replace_mapping' => ['a' => ['b']]]);
    }
}
