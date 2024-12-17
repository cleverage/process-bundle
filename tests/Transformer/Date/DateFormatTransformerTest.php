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

namespace CleverAge\ProcessBundle\Tests\Transformer\Date;

use CleverAge\ProcessBundle\Transformer\Date\DateFormatTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Transformer\Date\DateFormatTransformer
 */
class DateFormatTransformerTest extends TestCase
{
    /**
     * @covers ::transform
     */
    public function testTransformValidDate(): void
    {
        $transformer = new DateFormatTransformer();
        $value = new \DateTime('2023-09-28');
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertIsString($transformedValue);
        $this->assertEquals('2023-09-28', $transformedValue);
    }

    /**
     * @covers ::transform
     */
    public function testTransformInvalidDate(): void
    {
        $transformer = new DateFormatTransformer();
        $value = 'invalid_date';
        $options = ['format' => 'Y-m-d'];

        $this->expectException(\UnexpectedValueException::class);

        $transformer->transform($value, $options);
    }

    /**
     * @covers ::transform
     */
    public function testTransformNullValue(): void
    {
        // Arrange
        $transformer = new DateFormatTransformer();
        $value = null;
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertNull($transformedValue);
    }

    /**
     * @covers ::getCode
     */
    public function testGetCode(): void
    {
        $transformer = new DateFormatTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('date_format', $code);
    }

    /**
     * @covers ::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $transformer = new DateFormatTransformer();
        $resolver = new OptionsResolver();
        $resolver->setDefault('format', 'd/m/Y');

        $transformer->configureOptions($resolver);

        $this->assertTrue($resolver->isRequired('format'));

        $resolvedOptions = $resolver->resolve();
        $this->assertEquals(['format'], array_keys($resolvedOptions));
    }
}
