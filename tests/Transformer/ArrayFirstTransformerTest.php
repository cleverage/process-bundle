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

use CleverAge\ProcessBundle\Transformer\ArrayFirstTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayFirstTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ArrayFirstTransformer::transform
     */
    public function testTransformReturnsFirstElementIfIterableAndAllowed(): void
    {
        $transformer = new ArrayFirstTransformer();
        $value = [1, 2, 3];
        $options = ['allow_not_iterable' => false];

        $result = $transformer->transform($value, $options);

        $this->assertEquals(1, $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ArrayFirstTransformer::transform
     */
    public function testTransformReturnsValueIfNotIterableAndAllowed(): void
    {
        $this->expectException(\TypeError::class);

        $transformer = new ArrayFirstTransformer();
        $value = 'not_iterable_value';
        $options = ['allow_not_iterable' => true];

        $result = $transformer->transform($value, $options);

        $this->assertEquals('not_iterable_value', $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ArrayFirstTransformer::transform
     */
    public function testTransformThrowsExceptionIfNotIterableAndNotAllowed(): void
    {
        $transformer = new ArrayFirstTransformer();
        $value = 'not_iterable_value';
        $options = ['allow_not_iterable' => false];

        $result = $transformer->transform($value, $options);

        $this->assertEquals($value, $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ArrayFirstTransformer::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new ArrayFirstTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('array_first', $code);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ArrayFirstTransformer::configureOptions
     */
    public function testConfigureOptionsSetsDefaultOptions(): void
    {
        $resolver = new OptionsResolver();
        $transformer = new ArrayFirstTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['allow_not_iterable' => false], $resolvedOptions);
    }
}
