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

use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\Date\DateParserTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(DateParserTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(DateParserTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(DateParserTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\CoversMethod(DateParserTransformer::class, 'configureOptions')]
class DateParserTransformerTest extends TestCase
{
    public function testTransformValidDate(): void
    {
        $transformer = new DateParserTransformer();
        $value = '2023-09-28';
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertInstanceOf(\DateTime::class, $transformedValue);
        $this->assertEquals('2023-09-28', $transformedValue->format('Y-m-d'));
    }

    public function testTransformInvalidDate(): void
    {
        $transformer = new DateParserTransformer();
        $value = 'invalid_date';
        $options = ['format' => 'Y-m-d'];

        $this->expectException(\UnexpectedValueException::class);

        $transformer->transform($value, $options);
    }

    public function testTransformNullValue(): void
    {
        $transformer = new DateParserTransformer();
        $value = null;
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertNull($transformedValue);
    }

    public function testTransformDateTimeObject(): void
    {
        // Arrange
        $transformer = new DateParserTransformer();
        $value = new \DateTime('2023-09-28');
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertSame($value, $transformedValue);
    }

    public function testTransformDateTimeImmutableObject(): void
    {
        $transformer = new DateParserTransformer();
        $value = new \DateTimeImmutable('2023-09-28 12:34:56', new \DateTimeZone('Europe/Paris'));
        $options = ['format' => 'Y-m-d'];

        $transformedValue = $transformer->transform($value, $options);

        $this->assertInstanceOf(\DateTime::class, $transformedValue);
        $this->assertEquals($value, \DateTimeImmutable::createFromMutable($transformedValue));
        $this->assertSame('Europe/Paris', $transformedValue->getTimezone()->getName());
    }

    public function testGetCode(): void
    {
        $transformer = new DateParserTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('date_parser', $code);
    }

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

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideOutOfRangeDates(): iterable
    {
        yield 'day' => ['2024-02-31', 'Y-m-d', 'The parsed date was invalid'];
        yield 'month' => ['2024-13-01', 'Y-m-d', 'The parsed date was invalid'];
        yield 'time' => ['2024-02-01 25:61', 'Y-m-d H:i', 'The parsed time was invalid'];
    }

    /**
     * Parsed with a warning, the date overflowing: "2024-02-31" gave 2024-03-02.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideOutOfRangeDates')]
    public function testTransformOutOfRangeDate(string $value, string $format, string $warning): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Given value \"{$value}\" is not a valid date: {$warning}");

        (new DateParserTransformer())->transform($value, ['format' => $format]);
    }

    public function testTransformLeapDay(): void
    {
        $date = (new DateParserTransformer())->transform('2024-02-29', ['format' => '!Y-m-d']);

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame('2024-02-29', $date->format('Y-m-d'));
    }

    public function testTransformScalarAndStringable(): void
    {
        $transformer = new DateParserTransformer();
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return '2024-02-29';
            }
        };

        $timestamp = $transformer->transform(1709164800, ['format' => 'U']);
        $this->assertInstanceOf(\DateTime::class, $timestamp);
        $this->assertSame('2024-02-29', $timestamp->format('Y-m-d'));
        $fromStringable = $transformer->transform($stringable, ['format' => 'Y-m-d']);
        $this->assertInstanceOf(\DateTime::class, $fromStringable);
        $this->assertSame('2024-02-29', $fromStringable->format('Y-m-d'));
    }

    /**
     * A raw TypeError was thrown.
     */
    public function testTransformInvalidType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The date_parser transformer expects a string, array given.');

        (new DateParserTransformer())->transform(['2024-02-29'], ['format' => 'Y-m-d']);
    }
}
