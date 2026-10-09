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

use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(CallbackTransformer::class)]
class CallbackTransformerTest extends TestCase
{
    public static function doCallback(mixed ...$args): string
    {
        return implode('-', $args);
    }

    public function testGetCode(): void
    {
        self::assertSame('callback', (new CallbackTransformer())->getCode());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, mixed}>
     */
    public static function callbackProvider(): iterable
    {
        yield 'php function' => [['callback' => 'strtoupper'], 'foo', 'FOO'];
        yield 'static method as array' => [['callback' => [self::class, 'doCallback']], '3', '3'];
        yield 'static method as string' => [['callback' => self::class.'::doCallback'], '3', '3'];
        yield 'left parameters' => [
            ['callback' => [self::class, 'doCallback'], 'left_parameters' => [1, 2]],
            '3',
            '1-2-3',
        ];
        yield 'right parameters' => [
            ['callback' => [self::class, 'doCallback'], 'right_parameters' => [4, 5]],
            '3',
            '3-4-5',
        ];
        yield 'left and right parameters' => [
            ['callback' => [self::class, 'doCallback'], 'left_parameters' => [1, 2], 'right_parameters' => [4, 5]],
            '3',
            '1-2-3-4-5',
        ];
        yield 'value in second position' => [
            ['callback' => 'explode', 'left_parameters' => [',']],
            'a,b',
            ['a', 'b'],
        ];
        yield 'extra right parameter' => [
            ['callback' => 'json_decode', 'right_parameters' => [true]],
            '{"a":1}',
            ['a' => 1],
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('callbackProvider')]
    public function testTransform(array $options, mixed $value, mixed $expected): void
    {
        $transformer = new CallbackTransformer();

        self::assertSame($expected, $transformer->transform($value, $this->resolveOptions($transformer, $options)));
    }

    public function testAdditionalParametersAreUsedAsRightParameters(): void
    {
        $transformer = new CallbackTransformer();
        $options = $this->resolveOptions($transformer, [
            'callback' => [self::class, 'doCallback'],
            'additional_parameters' => [7, 8],
        ]);

        self::assertSame('6-7-8', $transformer->transform('6', $options));
    }

    public function testAdditionalParametersAreIgnoredWhenRightParametersAreSet(): void
    {
        $transformer = new CallbackTransformer();
        $options = $this->resolveOptions($transformer, [
            'callback' => [self::class, 'doCallback'],
            'right_parameters' => [4],
            'additional_parameters' => [7, 8],
        ]);

        self::assertSame('6-4', $transformer->transform('6', $options));
    }

    public function testCallbackIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions(new CallbackTransformer(), []);
    }

    public function testNonCallableCallbackIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Callback option must be callable');

        $this->resolveOptions(new CallbackTransformer(), ['callback' => 'not_a_function']);
    }

    public function testClosureCallbackIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new CallbackTransformer(), ['callback' => strtoupper(...)]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(CallbackTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
