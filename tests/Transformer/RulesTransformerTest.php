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
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(RulesTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversTrait(TransformerTrait::class)]
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
        $registry = new TransformerRegistry();
        $registry->addTransformer(new ArrayLastTransformer());

        return new RulesTransformer($registry, new ExpressionLanguage());
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
