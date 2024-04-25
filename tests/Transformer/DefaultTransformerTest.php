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

use CleverAge\ProcessBundle\Transformer\DefaultTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DefaultTransformer::transform
     */
    public function testTransformWithNonNullValue(): void
    {
        $transformer = new DefaultTransformer();
        $value = 'my_value';
        $options = ['value' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertSame($value, $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DefaultTransformer::transform
     */
    public function testTransformWithNullValue(): void
    {
        $transformer = new DefaultTransformer();
        $value = null;
        $options = ['value' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('default_value', $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DefaultTransformer::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('value', 'default_value');

        $transformer = new DefaultTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['value'], array_keys($resolvedOptions));
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DefaultTransformer::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new DefaultTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('default', $code);
    }
}
