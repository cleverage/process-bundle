<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\WrapperTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WrapperTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\WrapperTransformer::transform
     */
    public function testTransform(): void
    {
        $transformer = new WrapperTransformer();
        $value = 'my_value';
        $options = [
            'wrapper_key' => 'key',
        ];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals(['key' => 'my_value'], $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\WrapperTransformer::transform
     */
    public function testTransformWithIntegerWrapperKey(): void
    {
        $transformer = new WrapperTransformer();
        $value = 'my_value';
        $options = [
            'wrapper_key' => 1,
        ];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals([1 => 'my_value'], $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\WrapperTransformer::transform
     */
    public function testTransformWithNullValue(): void
    {
        $transformer = new WrapperTransformer();
        $value = null;
        $options = [
            'wrapper_key' => 'key',
        ];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals(['key' => null], $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\WrapperTransformer::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new WrapperTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('wrapper', $code);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\WrapperTransformer::configureOptions
     */
    public function testConfigureOptionsSetsDefaultOptions(): void
    {
        $resolver = new OptionsResolver();
        $transformer = new WrapperTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['wrapper_key'], array_keys($resolvedOptions));
    }
}
