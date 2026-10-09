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

use CleverAge\ProcessBundle\Transformer\ConditionTrait;
use CleverAge\ProcessBundle\Transformer\UnsetTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(UnsetTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesTrait(ConditionTrait::class)]
class UnsetTransformerTest extends TestCase
{
    private const INPUT = [
        'other' => 1,
        'to_unset' => 1,
        'to_test' => 2,
    ];

    public function testGetCode(): void
    {
        self::assertSame('unset', $this->createTransformer()->getCode());
    }

    public function testSimpleUnset(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property' => 'to_unset']);

        self::assertSame(['other' => 1, 'to_test' => 2], $transformer->transform(self::INPUT, $options));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>, bool}>
     */
    public static function conditionProvider(): iterable
    {
        yield 'match met' => [['match' => ['[to_test]' => 2]], self::INPUT, true];
        yield 'match not met' => [['match' => ['[to_test]' => 3]], self::INPUT, false];
        yield 'not_match met' => [['not_match' => ['[to_test]' => 3]], self::INPUT, true];
        yield 'not_match not met' => [['not_match' => ['[to_test]' => 2]], self::INPUT, false];
        yield 'match null not met' => [['match' => ['[to_test]' => null]], self::INPUT, false];
        yield 'match null met' => [['match' => ['[to_test]' => null]], ['to_unset' => 1, 'to_test' => null], true];
        yield 'empty met' => [['empty' => ['[to_test]' => null]], ['to_unset' => 1, 'to_test' => ''], true];
        yield 'empty not met' => [['empty' => ['[to_test]' => null]], self::INPUT, false];
        yield 'not_empty met' => [['not_empty' => ['[to_test]' => null]], self::INPUT, true];
        yield 'match_regexp met' => [['match_regexp' => ['[to_test]' => '/^\d$/']], self::INPUT, true];
        yield 'not_match_regexp not met' => [['not_match_regexp' => ['[to_test]' => '/^\d$/']], self::INPUT, false];
        yield 'all conditions must be met' => [
            ['match' => ['[to_test]' => 2], 'not_empty' => ['[other]' => null], 'empty' => ['[other]' => null]],
            self::INPUT,
            false,
        ];
    }

    /**
     * @param array<string, mixed> $condition
     * @param array<string, mixed> $input
     */
    #[DataProvider('conditionProvider')]
    public function testConditionalUnset(array $condition, array $input, bool $shouldUnset): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property' => 'to_unset', 'condition' => $condition]);

        $result = $transformer->transform($input, $options);

        self::assertSame(!$shouldUnset, \array_key_exists('to_unset', $result));
        unset($input['to_unset']);
        self::assertSame($input, array_diff_key($result, ['to_unset' => true]));
    }

    public function testNonArrayInputThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property' => 'to_unset']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Given value must be an array');

        $transformer->transform('not an array', $options);
    }

    public function testMissingPropertyThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, ['property' => 'to_unset']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Property to_unset does not exists');

        $transformer->transform(['no property found'], $options);
    }

    public function testMissingPropertyThrowsEvenWhenConditionIsNotMet(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'property' => 'missing',
            'condition' => ['match' => ['[to_test]' => 'never']],
        ]);

        $this->expectException(\UnexpectedValueException::class);

        $transformer->transform(self::INPUT, $options);
    }

    public function testPropertyIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions($this->createTransformer(), []);
    }

    public function testPropertyMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($this->createTransformer(), ['property' => 1]);
    }

    public function testUnknownConditionIsRejected(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $this->resolveOptions($this->createTransformer(), ['property' => 'a', 'condition' => ['unknown' => []]]);
    }

    private function createTransformer(): UnsetTransformer
    {
        return new UnsetTransformer(PropertyAccess::createPropertyAccessor());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(UnsetTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
