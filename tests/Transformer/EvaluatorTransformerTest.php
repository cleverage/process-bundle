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

use CleverAge\ProcessBundle\Transformer\EvaluatorTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(EvaluatorTransformer::class)]
class EvaluatorTransformerTest extends TestCase
{
    public function testExpressionIsEvaluatedWithInputAsVariables(): void
    {
        $transformer = new EvaluatorTransformer();
        $options = $this->resolveOptions($transformer, ['expression' => 'price * quantity']);

        self::assertSame('price * quantity', $options['expression']);
        self::assertSame(30, $transformer->transform(['price' => 10, 'quantity' => 3], $options));
        self::assertSame(7.5, $transformer->transform(['price' => 2.5, 'quantity' => 3], $options));
    }

    public function testExpressionIsParsedOnceWhenVariablesAreDefined(): void
    {
        $transformer = new EvaluatorTransformer();
        $options = $this->resolveOptions($transformer, [
            'expression' => 'price * quantity',
            'variables' => ['price', 'quantity'],
        ]);

        self::assertInstanceOf(ParsedExpression::class, $options['expression']);
        self::assertSame(30, $transformer->transform(['price' => 10, 'quantity' => 3], $options));
        self::assertSame(4, $transformer->transform(['price' => 2, 'quantity' => 2], $options));
    }

    public function testParsedExpressionIsAccepted(): void
    {
        $transformer = new EvaluatorTransformer();
        $expression = (new ExpressionLanguage())->parse('name ~ "!"', ['name']);
        $options = $this->resolveOptions($transformer, ['expression' => $expression]);

        self::assertSame($expression, $options['expression']);
        self::assertSame('foo!', $transformer->transform(['name' => 'foo'], $options));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, mixed}>
     */
    public static function expressionProvider(): iterable
    {
        yield 'comparison' => ['value > 10', ['value' => 12], true];
        yield 'ternary' => ['value ? "yes" : "no"', ['value' => false], 'no'];
        yield 'array access' => ['item["name"]', ['item' => ['name' => 'foo']], 'foo'];
        yield 'constant expression with empty input' => ['1 + 1', [], 2];
        yield 'built-in function' => ['constant("PHP_INT_MAX")', [], \PHP_INT_MAX];
    }

    /**
     * @param array<string, mixed> $value
     */
    #[DataProvider('expressionProvider')]
    public function testTransform(string $expression, array $value, mixed $expected): void
    {
        $transformer = new EvaluatorTransformer();

        self::assertSame($expected, $transformer->transform($value, $this->resolveOptions($transformer, ['expression' => $expression])));
    }

    public function testUndeclaredVariableThrowsWhenOptionsAreResolved(): void
    {
        $transformer = new EvaluatorTransformer();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Variable "quantity" is not valid');

        $this->resolveOptions($transformer, ['expression' => 'price * quantity', 'variables' => ['price']]);
    }

    public function testUndefinedVariableThrowsOnEvaluation(): void
    {
        $transformer = new EvaluatorTransformer();
        $options = $this->resolveOptions($transformer, ['expression' => 'price * quantity']);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Variable "quantity" is not valid');

        $transformer->transform(['price' => 10], $options);
    }

    public function testBundleExpressionFunctionsAreNotAvailable(): void
    {
        $transformer = new EvaluatorTransformer();
        $options = $this->resolveOptions($transformer, ['expression' => 'preg_match("/a/", value)']);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The function "preg_match" does not exist');

        $transformer->transform(['value' => 'abc'], $options);
    }

    public function testNonArrayInputThrowsTypeError(): void
    {
        $transformer = new EvaluatorTransformer();
        $options = $this->resolveOptions($transformer, ['expression' => '1 + 1']);

        $this->expectException(\TypeError::class);

        $transformer->transform('not an array', $options);
    }

    public function testExpressionIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions(new EvaluatorTransformer(), []);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'expression not a string' => [['expression' => 42]];
        yield 'variables not an array' => [['expression' => '1', 'variables' => 'foo']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrow(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new EvaluatorTransformer(), $options);
    }

    public function testGetCode(): void
    {
        self::assertSame('evaluator', (new EvaluatorTransformer())->getCode());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(EvaluatorTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
