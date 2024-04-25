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

use CleverAge\ProcessBundle\Transformer\TrimTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrimTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::transform
     */
    public function testTransformTrimsStringWithDefaultCharlist(): void
    {
        $transformer = new TrimTransformer();
        $value = '  trim me  ';

        $result = $transformer->transform($value);

        $this->assertEquals('trim me', $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::transform
     */
    public function testTransformTrimsStringWithCustomCharlist(): void
    {
        $transformer = new TrimTransformer();
        $value = '-trim me-';
        $options = ['charlist' => '-'];

        $result = $transformer->transform($value, $options);

        $this->assertEquals('trim me', $result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::transform
     */
    public function testTransformReturnsNullForNullValue(): void
    {
        $transformer = new TrimTransformer();
        $value = null;

        $result = $transformer->transform($value);

        $this->assertNull($result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new TrimTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('trim', $code);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::configureOptions
     */
    public function testConfigureOptionsSetsDefaultOptions(): void
    {
        $resolver = new OptionsResolver();
        $transformer = new TrimTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['charlist' => " \t\n\r\0\x0B"], $resolvedOptions);
    }
}
