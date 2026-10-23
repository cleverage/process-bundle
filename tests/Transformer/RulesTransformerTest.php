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

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\Array\ArrayLastTransformer;
use CleverAge\ProcessBundle\Transformer\RulesTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(RulesTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversTrait(TransformerTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ArrayLastTransformer::class)]
class RulesTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'rules_set' => [
                ['condition' => 'value == "foo"', 'constant' => 'is foo'],
                ['default' => true, 'constant' => 'is not foo'],
            ],
        ]);

        $this->assertSame('is foo', $transformer->transform('foo', $options));
        $this->assertSame('is not foo', $transformer->transform('bar', $options));
    }

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public static function nonBooleanConditionProvider(): iterable
    {
        yield 'preg_match int match' => ['preg_match("/^foo/", value)', 'foobar', 'matched'];
        yield 'preg_match int no match' => ['preg_match("/^foo/", value)', 'barfoo', 'default'];
        yield 'matches operator' => ['value matches "/^foo/"', 'foobar', 'matched'];
        yield 'matches operator no match' => ['value matches "/^foo/"', 'barfoo', 'default'];
        yield 'truthy string variable' => ['value', 'foo', 'matched'];
        yield 'falsy string variable' => ['value', '0', 'default'];
        yield 'null variable' => ['value', null, 'default'];
        yield 'truthy array variable' => ['value', ['a'], 'matched'];
        yield 'empty array variable' => ['value', [], 'default'];
    }

    #[DataProvider('nonBooleanConditionProvider')]
    public function testNonBooleanCondition(string $condition, mixed $input, string $expected): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'rules_set' => [
                ['condition' => $condition, 'constant' => 'matched'],
                ['default' => true, 'constant' => 'default'],
            ],
        ]);

        $this->assertSame($expected, $transformer->transform($input, $options));
    }

    public function testNonBooleanConditionWithValueAsVariables(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'use_value_as_variables' => true,
            'expression_variables' => ['name'],
            'rules_set' => [
                ['condition' => 'name', 'constant' => 'matched'],
                ['default' => true, 'constant' => 'default'],
            ],
        ]);

        $this->assertSame('matched', $transformer->transform(['name' => 'foo'], $options));
        $this->assertSame('default', $transformer->transform(['name' => null], $options));
    }

    public function testParsedExpressionCondition(): void
    {
        $transformer = $this->createTransformer();
        $parsed = (new ExpressionLanguage())->parse('value', ['value']);
        $this->assertInstanceOf(ParsedExpression::class, $parsed);
        $options = $this->resolveOptions($transformer, [
            'rules_set' => [
                ['condition' => $parsed, 'constant' => 'matched'],
                ['default' => true, 'constant' => 'default'],
            ],
        ]);

        $this->assertSame('matched', $transformer->transform('foo', $options));
        $this->assertSame('default', $transformer->transform(null, $options));
    }

    public function testRulesSetCannotHaveMoreThanOneDefaultRule(): void
    {
        $transformer = $this->createTransformer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rules set cannot have more than one default rule');

        $this->resolveOptions($transformer, [
            'rules_set' => [
                ['default' => true, 'constant' => 'first'],
                ['default' => true, 'constant' => 'second'],
            ],
        ]);
    }

    public function testNonConfigurableTransformerCannotHaveOptions(): void
    {
        $transformer = $this->createTransformer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transformer array_last should not have options');

        $this->resolveOptions($transformer, [
            'rules_set' => [
                ['default' => true, 'transformers' => ['array_last' => ['foo' => 'bar']]],
            ],
        ]);
    }

    private function createTransformer(): RulesTransformer
    {
        $registry = new TransformerRegistry(new ServiceLocator([
            'array_last' => static fn (): ArrayLastTransformer => new ArrayLastTransformer(),
        ]));

        $language = new ExpressionLanguage();
        $language->addFunction(ExpressionFunction::fromPhp('preg_match'));

        return new RulesTransformer($registry, $language);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(RulesTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
