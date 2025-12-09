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

namespace CleverAge\ProcessBundle\Tests\Transformer\String;

use CleverAge\ProcessBundle\Transformer\String\ExplodeTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ExplodeTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(ExplodeTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(ExplodeTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\CoversMethod(ExplodeTransformer::class, 'configureOptions')]
class ExplodeTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new ExplodeTransformer();

        $result = $transformer->transform('1,2,3', ['delimiter' => ',']);

        $this->assertEquals(['1', '2', '3'], $result);
    }

    public function testTransformWithEmptyString(): void
    {
        $transformer = new ExplodeTransformer();

        $result = $transformer->transform('', ['delimiter' => ',']);

        $this->assertEquals([], $result);
    }

    public function testTransformWithNullValue(): void
    {
        $transformer = new ExplodeTransformer();

        $result = $transformer->transform(null, ['delimiter' => ',']);

        $this->assertEquals([], $result);
    }

    public function testGetCode(): void
    {
        $transformer = new ExplodeTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('explode', $code);
    }

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
