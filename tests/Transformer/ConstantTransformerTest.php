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

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\ConstantTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Transformer\ConstantTransformer
 */
class ConstantTransformerTest extends TestCase
{
    /**
     * @covers ::transform
     */
    public function testTransform(): void
    {
        $transformer = new ConstantTransformer();
        $value = 'my_value';
        $options = ['constant' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('default_value', $transformedValue);
    }

    /**
     * @covers ::transform
     */
    public function testTransformWithNullValue(): void
    {
        $transformer = new ConstantTransformer();
        $value = null;
        $options = ['constant' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('default_value', $transformedValue);
    }

    /**
     * @covers ::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $transformer = new ConstantTransformer();
        $resolver = new OptionsResolver();

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('constant'));
    }

    /**
     * @covers ::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new ConstantTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('constant', $code);
    }
}
