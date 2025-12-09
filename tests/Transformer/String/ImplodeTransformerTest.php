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

use CleverAge\ProcessBundle\Transformer\String\ImplodeTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ImplodeTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(ImplodeTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(ImplodeTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\CoversMethod(ImplodeTransformer::class, 'configureOptions')]
class ImplodeTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new ImplodeTransformer();

        $result = $transformer->transform(['1', '2', '3'], ['separator' => ',']);

        $this->assertEquals('1,2,3', $result);
    }

    public function testTransformWithInvalidValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $transformer = new ImplodeTransformer();

        $transformer->transform('invalid_value', ['separator' => ',']);
    }

    public function testTransformWithDefaultSeparator(): void
    {
        $transformer = new ImplodeTransformer();

        $result = $transformer->transform(['1', '2', '3'], ['separator' => '|']);

        $this->assertEquals('1|2|3', $result);
    }

    public function testGetCode(): void
    {
        $transformer = new ImplodeTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('implode', $code);
    }

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
