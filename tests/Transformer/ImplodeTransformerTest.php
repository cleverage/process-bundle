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

use CleverAge\ProcessBundle\Transformer\ImplodeTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

class ImplodeTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ImplodeTransformer::transform
     */
    public function testTransform(): void
    {
        $transformer = new ImplodeTransformer();

        $result = $transformer->transform(['1', '2', '3'], ['separator' => ',']);

        $this->assertEquals('1,2,3', $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ImplodeTransformer::transform
     */
    public function testTransformWithInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $transformer = new ImplodeTransformer();

        $transformer->transform('invalid_value', ['separator' => ',']);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ImplodeTransformer::transform
     */
    public function testTransformWithDefaultSeparator(): void
    {
        $transformer = new ImplodeTransformer();

        $result = $transformer->transform(['1', '2', '3'], ['separator' => '|']);

        $this->assertEquals('1|2|3', $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ImplodeTransformer::getCode
     */
    public function testGetCode(): void
    {
        $transformer = new ImplodeTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('implode', $code);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\ImplodeTransformer::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $transformer = new ImplodeTransformer();
        $resolver = new OptionsResolver();

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('separator'));

        $resolvedOptions = $resolver->resolve();
        $this->assertEquals(['separator'], array_keys($resolvedOptions));
    }
}
