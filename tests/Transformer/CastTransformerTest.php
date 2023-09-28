<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\CastTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ValueError;

class CastTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::transform
     */
    public function testCastToInt(): void
    {
        $transformer = new CastTransformer();
        $value = '123';
        $options = ['type' => 'int'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsInt($transformedValue);
        $this->assertEquals(123, $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::transform
     */
    public function testCastToFloat(): void
    {
        $transformer = new CastTransformer();
        $value = '123.45';
        $options = ['type' => 'float'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsFloat($transformedValue);
        $this->assertEquals(123.45, $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::transform
     */
    public function testCastToString(): void
    {
        $transformer = new CastTransformer();
        $value = 123;
        $options = ['type' => 'string'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsString($transformedValue);
        $this->assertEquals('123', $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::transform
     */
    public function testCastToBool(): void
    {
        $transformer = new CastTransformer();
        $value = 'true';
        $options = ['type' => 'bool'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsBool($transformedValue);
        $this->assertTrue($transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::transform
     */
    public function testCastToInvalidType(): void
    {
        $transformer = new CastTransformer();
        $value = '123';
        $options = ['type' => 'invalid_type'];

        $this->expectException(ValueError::class);

        $transformer->transform($value, $options);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::configureOptions
     */
    public function testConfigureOptionsSetsRequiredOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('type', 'int');

        $transformer = new CastTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['type'], array_keys($resolvedOptions));
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\CastTransformer::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new CastTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('cast', $code);
    }
}
