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

use CleverAge\ProcessBundle\Transformer\CastTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(CastTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(CastTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(CastTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(CastTransformer::class, 'getCode')]
class CastTransformerTest extends TestCase
{
    public function testCastToInt(): void
    {
        $transformer = new CastTransformer();
        $value = '123';
        $options = ['type' => 'int'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsInt($transformedValue);
        $this->assertEquals(123, $transformedValue);
    }

    public function testCastToFloat(): void
    {
        $transformer = new CastTransformer();
        $value = '123.45';
        $options = ['type' => 'float'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsFloat($transformedValue);
        $this->assertEquals(123.45, $transformedValue);
    }

    public function testCastToString(): void
    {
        $transformer = new CastTransformer();
        $value = 123;
        $options = ['type' => 'string'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsString($transformedValue);
        $this->assertEquals('123', $transformedValue);
    }

    public function testCastToBool(): void
    {
        $transformer = new CastTransformer();
        $value = 'true';
        $options = ['type' => 'bool'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsBool($transformedValue);
        $this->assertTrue($transformedValue);
    }

    public function testCastToInvalidType(): void
    {
        $transformer = new CastTransformer();
        $value = '123';
        $options = ['type' => 'invalid_type'];

        $this->expectException(\ValueError::class);

        $transformer->transform($value, $options);
    }

    public function testConfigureOptionsSetsRequiredOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('type', 'int');

        $transformer = new CastTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['type'], array_keys($resolvedOptions));
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new CastTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('cast', $code);
    }
}
