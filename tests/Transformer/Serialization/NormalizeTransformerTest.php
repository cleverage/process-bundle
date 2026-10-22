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

use CleverAge\ProcessBundle\Transformer\Serialization\NormalizeTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[\PHPUnit\Framework\Attributes\CoversClass(NormalizeTransformer::class)]
class NormalizeTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('normalize', $this->createTransformer()->getCode());
    }

    public function testObjectIsNormalizedWithDefaultOptions(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, []);

        self::assertSame(
            ['name' => 'foo', 'tags' => ['a', 'b']],
            $transformer->transform((object) ['name' => 'foo', 'tags' => ['a', 'b']], $options)
        );
    }

    public function testNestedObjectsAreNormalized(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, []);

        self::assertSame(
            ['date' => '2024-01-02T00:00:00+00:00'],
            $transformer->transform(
                (object) ['date' => new \DateTimeImmutable('2024-01-02', new \DateTimeZone('UTC'))],
                $options
            )
        );
    }

    public function testContextIsPassedToTheNormalizer(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'context' => [AbstractNormalizer::ATTRIBUTES => ['name']],
        ]);

        self::assertSame(['name' => 'foo'], $transformer->transform(new class {
            public string $name = 'foo';
            public string $secret = 'bar';
        }, $options));
    }

    public function testFormatAndContextArePassedToTheNormalizer(): void
    {
        $normalizer = new class implements NormalizerInterface {
            public function normalize(mixed $data, ?string $format = null, array $context = []): array
            {
                return ['data' => $data, 'format' => $format, 'context' => $context];
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function getSupportedTypes(?string $format): array
            {
                return ['*' => true];
            }
        };
        $transformer = new NormalizeTransformer($normalizer);
        $options = $this->resolveOptions($transformer, ['format' => 'json', 'context' => ['foo' => 'bar']]);

        self::assertSame(
            ['data' => 'value', 'format' => 'json', 'context' => ['foo' => 'bar']],
            $transformer->transform('value', $options)
        );
    }

    public function testUnsupportedValueThrows(): void
    {
        $transformer = new NormalizeTransformer(new Serializer([new DateTimeNormalizer()]));
        $options = $this->resolveOptions($transformer, []);

        $this->expectException(NotNormalizableValueException::class);

        $transformer->transform(new \stdClass(), $options);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'format not a string' => [['format' => 1]];
        yield 'context not an array' => [['context' => 'foo']];
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

    private function createTransformer(): NormalizeTransformer
    {
        return new NormalizeTransformer(new Serializer([new DateTimeNormalizer(), new ObjectNormalizer()]));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(NormalizeTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
