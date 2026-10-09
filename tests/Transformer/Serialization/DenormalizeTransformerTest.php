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

namespace CleverAge\ProcessBundle\Tests\Transformer\Serialization;

use CleverAge\ProcessBundle\Transformer\Serialization\DenormalizeTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Serializer;

#[\PHPUnit\Framework\Attributes\CoversClass(DenormalizeTransformer::class)]
class DenormalizeTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('denormalize', $this->createTransformer()->getCode());
    }

    public function testValueIsDenormalizedIntoTheGivenClass(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \DateTimeImmutable::class]);

        $result = $transformer->transform('2024-01-02T03:04:05+00:00', $options);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-02T03:04:05+00:00', $result->format(\DATE_ATOM));
    }

    public function testCollectionClassIsSupported(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \DateTimeImmutable::class.'[]']);

        $result = $transformer->transform(['a' => '2024-01-02', 'b' => '2024-02-03'], $options);

        self::assertIsArray($result);
        self::assertSame(['a', 'b'], array_keys($result));
        self::assertContainsOnlyInstancesOf(\DateTimeImmutable::class, $result);
    }

    public function testContextIsPassedToTheDenormalizer(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'class' => \DateTimeImmutable::class,
            'context' => [DateTimeNormalizer::FORMAT_KEY => 'd/m/Y H:i', DateTimeNormalizer::TIMEZONE_KEY => 'UTC'],
        ]);

        $result = $transformer->transform('02/01/2024 03:04', $options);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-02T03:04:00+00:00', $result->format(\DATE_ATOM));
    }

    public function testClassFormatAndContextArePassedToTheDenormalizer(): void
    {
        $denormalizer = new class implements DenormalizerInterface {
            public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): array
            {
                return ['data' => $data, 'type' => $type, 'format' => $format, 'context' => $context];
            }

            public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function getSupportedTypes(?string $format): array
            {
                return ['*' => true];
            }
        };
        $transformer = new DenormalizeTransformer($denormalizer);
        $options = $this->resolveOptions($transformer, [
            'class' => 'App\Foo',
            'format' => 'json',
            'context' => ['foo' => 'bar'],
        ]);

        self::assertSame(
            ['data' => ['a' => 1], 'type' => 'App\Foo', 'format' => 'json', 'context' => ['foo' => 'bar']],
            $transformer->transform(['a' => 1], $options)
        );
    }

    public function testInvalidValueThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \DateTimeImmutable::class]);

        $this->expectException(NotNormalizableValueException::class);

        $transformer->transform('not a date', $options);
    }

    public function testClassIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions($this->createTransformer(), []);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'class not a string' => [['class' => 1]];
        yield 'format not a string' => [['class' => 'Foo', 'format' => 1]];
        yield 'context not an array' => [['class' => 'Foo', 'context' => 'foo']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsAreRejected(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($this->createTransformer(), $options);
    }

    private function createTransformer(): DenormalizeTransformer
    {
        return new DenormalizeTransformer(new Serializer([new ArrayDenormalizer(), new DateTimeNormalizer()]));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(DenormalizeTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
