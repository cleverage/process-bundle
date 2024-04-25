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

use CleverAge\ProcessBundle\Transformer\ArrayElementTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Transformer\ArrayElementTransformer
 */
class ArrayElementTransformerTest extends TestCase
{
    /**
     * @covers ::transform
     */
    public function testTransformReturnsNthElementFromArray(): void
    {
        $transformer = new ArrayElementTransformer();
        $value = ['foo', 'bar', 'baz'];
        $options = ['index' => 1];

        $result = $transformer->transform($value, $options);

        $this->assertEquals('bar', $result);
    }

    /**
     * @covers ::configureOptions
     */
    public function testConfigureOptionsSetsRequiredOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('index', 1);

        $transformer = new ArrayElementTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['index'], array_keys($resolvedOptions));
    }

    /**
     * @covers ::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new ArrayElementTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('array_element', $code);
    }
}
