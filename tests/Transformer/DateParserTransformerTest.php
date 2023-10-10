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

use CleverAge\ProcessBundle\Transformer\DateParserTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateParserTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DateParserTransformer::transform
     */
    public function testTransformValidDate(): void
    {
        $transformer = new DateParserTransformer();
        $value = '2023-09-28';
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertInstanceOf(\DateTime::class, $transformedValue);
        $this->assertEquals('2023-09-28', $transformedValue->format('Y-m-d'));
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DateParserTransformer::transform
     */
    public function testTransformInvalidDate(): void
    {
        $transformer = new DateParserTransformer();
        $value = 'invalid_date';
        $options = ['format' => 'Y-m-d'];

        $this->expectException(\UnexpectedValueException::class);

        $transformer->transform($value, $options);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DateParserTransformer::transform
     */
    public function testTransformNullValue(): void
    {
        $transformer = new DateParserTransformer();
        $value = null;
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertNull($transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DateParserTransformer::transform
     */
    public function testTransformDateTimeObject(): void
    {
        // Arrange
        $transformer = new DateParserTransformer();
        $value = new \DateTime('2023-09-28');
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertSame($value, $transformedValue);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DateParserTransformer::getCode
     */
    public function testGetCode(): void
    {
        $transformer = new DateParserTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('date_parser', $code);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DateParserTransformer::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $transformer = new DateParserTransformer();
        $resolver = new OptionsResolver();
        $resolver->setDefault('format', 'd/m/Y');

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('format'));

        $resolvedOptions = $resolver->resolve();
        $this->assertEquals(['format'], array_keys($resolvedOptions));
    }
}
