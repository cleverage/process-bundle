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

namespace CleverAge\ProcessBundle\Tests\Transformer\Array;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\Array\ArrayMapTransformer;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayMapTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesTrait(TransformerTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CallbackTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerException::class)]
class ArrayMapTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('array_map', $this->createTransformer()->getCode());
    }

    public function testTransformersAreAppliedToEachElementAndKeysArePreserved(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'transformers' => [
                'callback#trim' => ['callback' => 'trim'],
                'callback#upper' => ['callback' => 'strtoupper'],
            ],
        ]);

        self::assertSame(
            ['a' => 'FOO', 3 => 'BAR'],
            $transformer->transform(['a' => ' foo ', 3 => 'bar '], $options)
        );
    }

    public function testTraversableInputIsAccepted(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'transformers' => ['callback' => ['callback' => 'strtoupper']],
        ]);

        self::assertSame(['x' => 'A', 'y' => 'B'], $transformer->transform(new \ArrayIterator(['x' => 'a', 'y' => 'b']), $options));
    }

    public function testEmptyTransformersReturnTheElementsUnchanged(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, []);

        self::assertSame([1, null, 'a'], $transformer->transform([1, null, 'a'], $options));
    }

    public function testNullElementsAreKeptByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'transformers' => ['callback' => ['callback' => [self::class, 'nullIfEmpty']]],
        ]);

        self::assertSame([0 => 'a', 1 => null, 2 => 'b'], $transformer->transform(['a', '', 'b'], $options));
    }

    public function testSkipNullRemovesNullTransformedElements(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'skip_null' => true,
            'transformers' => ['callback' => ['callback' => [self::class, 'nullIfEmpty']]],
        ]);

        self::assertSame([0 => 'a', 2 => 'b'], $transformer->transform(['a', '', 'b'], $options));
    }

    public function testFailingTransformerReportsTheElementKey(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'transformers' => ['callback' => ['callback' => [self::class, 'failOnBar']]],
        ]);

        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage("For target property 'second', transformation 'callback' have failed: bar is not allowed");

        $transformer->transform(['first' => 'foo', 'second' => 'bar'], $options);
    }

    public function testNonIterableInputThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, []);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Input value must be an array or traversable');

        $transformer->transform('foo', $options);
    }

    public function testSkipNullMustBeABoolean(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($this->createTransformer(), ['skip_null' => 'yes']);
    }

    public static function nullIfEmpty(string $value): ?string
    {
        return '' === $value ? null : $value;
    }

    public static function failOnBar(string $value): string
    {
        if ('bar' === $value) {
            throw new \RuntimeException('bar is not allowed');
        }

        return $value;
    }

    private function createTransformer(): ArrayMapTransformer
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CallbackTransformer());

        return new ArrayMapTransformer($registry);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(ArrayMapTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
