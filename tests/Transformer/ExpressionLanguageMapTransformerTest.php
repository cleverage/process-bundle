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

use CleverAge\ProcessBundle\Transformer\ExpressionLanguageMapTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(ExpressionLanguageMapTransformer::class)]
class ExpressionLanguageMapTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new ExpressionLanguageMapTransformer(new ExpressionLanguage());
        $options = $this->resolveOptions($transformer);

        $this->assertSame('answer', $transformer->transform(42, $options));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function missingValueProvider(): iterable
    {
        yield 'scalar' => [3, "No expression accepting value '3' in map"];
        yield 'array' => [[3], "No expression accepting value 'array' in map"];
        yield 'object' => [new \stdClass(), "No expression accepting value 'stdClass' in map"];
        yield 'null' => [null, "No expression accepting value 'null' in map"];
    }

    #[DataProvider('missingValueProvider')]
    public function testMissingValueMessage(mixed $value, string $expectedMessage): void
    {
        $transformer = new ExpressionLanguageMapTransformer(new ExpressionLanguage());
        $options = $this->resolveOptions($transformer);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        $transformer->transform($value, $options);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveOptions(ExpressionLanguageMapTransformer $transformer): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve([
            'map' => [
                ['condition' => 'data === 42', 'output' => '"answer"'],
            ],
        ]);
    }
}
