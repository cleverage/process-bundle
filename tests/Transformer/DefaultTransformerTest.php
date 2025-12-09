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

use CleverAge\ProcessBundle\Transformer\DefaultTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(DefaultTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(DefaultTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(DefaultTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(DefaultTransformer::class, 'getCode')]
class DefaultTransformerTest extends TestCase
{
    public function testTransformWithNonNullValue(): void
    {
        $transformer = new DefaultTransformer();
        $value = 'my_value';
        $options = ['value' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertSame($value, $transformedValue);
    }

    public function testTransformWithNullValue(): void
    {
        $transformer = new DefaultTransformer();
        $value = null;
        $options = ['value' => 'default_value'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertEquals('default_value', $transformedValue);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('value', 'default_value');

        $transformer = new DefaultTransformer();

        $transformer->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['value'], array_keys($resolvedOptions));
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new DefaultTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('default', $code);
    }
}
