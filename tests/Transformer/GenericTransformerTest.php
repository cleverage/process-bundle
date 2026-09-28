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

use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\GenericTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(GenericTransformer::class)]
class GenericTransformerTest extends TestCase
{
    public function testRequiredOptionMustBeProvided(): void
    {
        $transformer = $this->createSubstrTransformer(['offset' => ['required' => true]]);

        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions($transformer, []);
    }

    public function testOptionWithDefault(): void
    {
        $transformer = $this->createSubstrTransformer([
            'offset' => ['default' => 2],
            'length' => ['default_is_null' => true],
        ]);

        $this->assertSame('llo', $transformer->transform('hello', $this->resolveOptions($transformer, [])));
        $this->assertSame('ll', $transformer->transform('hello', $this->resolveOptions($transformer, ['length' => 2])));
    }

    public function testOptionalOptionWithoutDefaultCanBeProvided(): void
    {
        $transformer = $this->createSubstrTransformer([
            'offset' => ['required' => true],
            'length' => ['required' => false],
        ]);

        $options = $this->resolveOptions($transformer, ['offset' => 1, 'length' => 3]);

        $this->assertSame('ell', $transformer->transform('hello', $options));
    }

    public function testOptionalOptionWithoutDefaultIsNullWhenOmitted(): void
    {
        $transformer = $this->createSubstrTransformer([
            'offset' => ['required' => true],
            'length' => ['required' => false],
        ]);

        $options = $this->resolveOptions($transformer, ['offset' => 1]);

        $this->assertSame('ello', $transformer->transform('hello', $options));
    }

    /**
     * Generic transformer applying substr($value, {{ offset }}, {{ length }}).
     *
     * @param array<string, array<string, mixed>> $contextualOptions
     */
    private function createSubstrTransformer(array $contextualOptions): GenericTransformer
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CallbackTransformer());

        $transformer = new GenericTransformer(new ContextualOptionResolver(), $registry);
        $transformer->initialize('substr', [
            'contextual_options' => $contextualOptions,
            'transformers' => [
                'callback' => [
                    'callback' => 'substr',
                    'right_parameters' => ['{{ offset }}', '{{ length }}'],
                ],
            ],
        ]);

        return $transformer;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(GenericTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
