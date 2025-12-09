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

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Transformer\ConstantTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ConstantTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(ConstantTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(ConstantTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(ConstantTransformer::class, 'getCode')]
class ConstantTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new ConstantTransformer();
        $value = 'my_value';
        $options = ['constant' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('default_value', $transformedValue);
    }

    public function testTransformWithNullValue(): void
    {
        $transformer = new ConstantTransformer();
        $value = null;
        $options = ['constant' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('default_value', $transformedValue);
    }

    public function testConfigureOptions(): void
    {
        $transformer = new ConstantTransformer();
        $resolver = new OptionsResolver();

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('constant'));
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new ConstantTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('constant', $code);
    }
}
