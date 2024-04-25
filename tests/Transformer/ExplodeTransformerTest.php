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

use CleverAge\ProcessBundle\Transformer\ExplodeTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Transformer\ExplodeTransformer
 */
class ExplodeTransformerTest extends TestCase
{
    /**
     * @covers ::transform
     */
    public function testTransform(): void
    {
        $transformer = new ExplodeTransformer();

        $result = $transformer->transform('1,2,3', ['delimiter' => ',']);

        $this->assertEquals(['1', '2', '3'], $result);
    }

    /**
     * @covers ::transform
     */
    public function testTransformWithEmptyString(): void
    {
        $transformer = new ExplodeTransformer();

        $result = $transformer->transform('', ['delimiter' => ',']);

        $this->assertEquals([], $result);
    }

    /**
     * @covers ::transform
     */
    public function testTransformWithNullValue(): void
    {
        $transformer = new ExplodeTransformer();

        $result = $transformer->transform(null, ['delimiter' => ',']);

        $this->assertEquals([], $result);
    }

    /**
     * @covers ::getCode
     */
    public function testGetCode(): void
    {
        $transformer = new ExplodeTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('explode', $code);
    }

    /**
     * @covers ::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $transformer = new ExplodeTransformer();
        $resolver = new OptionsResolver();
        $resolver->setDefault('delimiter', ',');

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('delimiter'));

        $resolvedOptions = $resolver->resolve();
        $this->assertEquals(['delimiter'], array_keys($resolvedOptions));
    }
}
