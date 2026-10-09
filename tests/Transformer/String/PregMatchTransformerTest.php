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

use CleverAge\ProcessBundle\Transformer\String\PregMatchTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(PregMatchTransformer::class)]
class PregMatchTransformerTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, mixed, array<mixed>}>
     */
    public static function transformProvider(): iterable
    {
        yield 'groups' => [['pattern' => '/(foo)(bar)(baz)/'], 'foobarbaz', ['foobarbaz', 'foo', 'bar', 'baz']];
        yield 'named groups' => [['pattern' => '/(?<word>[a-z]+)-(?<num>\d+)/'], 'abc-12', ['abc-12', 'word' => 'abc', 'abc', 'num' => '12', '12']];
        yield 'no match' => [['pattern' => '/\d+/'], 'abc', []];
        yield 'int input is cast' => [['pattern' => '/^(\d)(\d)$/'], 42, ['42', '4', '2']];
        yield 'zero string is not empty' => [['pattern' => '/\d/'], '0', ['0']];
        yield 'offset capture flag' => [
            ['pattern' => '/(foo)(bar)(baz)/', 'flags' => \PREG_OFFSET_CAPTURE],
            'foobarbaz',
            [['foobarbaz', 0], ['foo', 0], ['bar', 3], ['baz', 6]],
        ];
        yield 'offset' => [['pattern' => '/\d+/', 'offset' => 3], 'a1b22c333', ['22']];
        yield 'mode_all' => [['pattern' => '/\d+/', 'mode_all' => true], 'a1b22c333', [['1', '22', '333']]];
        yield 'mode_all with groups' => [['pattern' => '/([a-z])(\d+)/', 'mode_all' => true], 'a1b22', [['a1', 'b22'], ['a', 'b'], ['1', '22']]];
        yield 'mode_all without match' => [['pattern' => '/\d+/', 'mode_all' => true], 'abc', [[]]];
        yield 'mode_all with set order flag' => [
            ['pattern' => '/([a-z])(\d+)/', 'mode_all' => true, 'flags' => \PREG_SET_ORDER],
            'a1b22',
            [['a1', 'a', '1'], ['b22', 'b', '22']],
        ];
        yield 'mode_all with offset' => [['pattern' => '/\d+/', 'mode_all' => true, 'offset' => 3], 'a1b22c333', [['22', '333']]];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<mixed>         $expected
     */
    #[DataProvider('transformProvider')]
    public function testTransform(array $options, mixed $value, array $expected): void
    {
        $transformer = new PregMatchTransformer();

        self::assertSame($expected, $transformer->transform($value, $this->resolveOptions($transformer, $options)));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyValueProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    #[DataProvider('emptyValueProvider')]
    public function testEmptyValueReturnsNull(mixed $value): void
    {
        $transformer = new PregMatchTransformer();
        $options = $this->resolveOptions($transformer, ['pattern' => '/.*/']);

        self::assertNull($transformer->transform($value, $options));
        self::assertNull($transformer->transform($value, $this->resolveOptions($transformer, ['pattern' => '/.*/', 'mode_all' => true])));
    }

    public function testPatternIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions(new PregMatchTransformer(), []);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'pattern not a string' => [['pattern' => ['/a/']]];
        yield 'flags not an int' => [['pattern' => '/a/', 'flags' => '1']];
        yield 'offset not an int' => [['pattern' => '/a/', 'offset' => '1']];
        yield 'mode_all not a bool' => [['pattern' => '/a/', 'mode_all' => 1]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrow(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new PregMatchTransformer(), $options);
    }

    public function testGetCode(): void
    {
        self::assertSame('preg_match', (new PregMatchTransformer())->getCode());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(PregMatchTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
