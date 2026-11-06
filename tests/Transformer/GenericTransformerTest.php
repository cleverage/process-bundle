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

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\Array\ArrayFirstTransformer;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\GenericTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(GenericTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CallbackTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ArrayFirstTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(DeprecatedCodeAliases::class)]
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

    public function testTransformersCanBeGivenAsAList(): void
    {
        $registry = new TransformerRegistry(new ServiceLocator([
            'callback' => static fn (): CallbackTransformer => new CallbackTransformer(),
        ]));

        $transformer = new GenericTransformer(new ContextualOptionResolver(), $registry);
        $transformer->initialize('substr_upper', [
            'contextual_options' => ['offset' => ['required' => true]],
            'transformers' => [
                ['callback' => ['callback' => 'substr', 'right_parameters' => ['{{ offset }}']]],
                ['callback' => ['callback' => 'strtoupper']],
            ],
        ]);

        $this->assertSame('LLO', $transformer->transform('hello', $this->resolveOptions($transformer, ['offset' => 2])));
    }

    public function testGetCodeRequiresInitialization(): void
    {
        $transformer = new GenericTransformer(new ContextualOptionResolver(), new TransformerRegistry());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The generic transformer is not initialized');

        $transformer->getCode();
    }

    /**
     * Before 5.9, the dashes of the transformer codes of a generic transformer were replaced by underscores: a code
     * that is not registered with its dashes but is with underscores still works, with a deprecation.
     */
    public function testFormerNameOfATransformerCodeIsDeprecated(): void
    {
        $transformer = new GenericTransformer(new ContextualOptionResolver(), $this->createRegistry());
        $deprecations = $this->collectDeprecations(static fn () => $transformer->initialize('first_upper', [
            'transformers' => [
                'array-first' => null,
                'callback#upper-case' => ['callback' => 'strtoupper'],
            ],
        ]));

        self::assertSame(
            ['Since cleverage/process-bundle 5.10: The transformer "array-first" of the generic transformer "first_upper" is not registered, its former name "array_first", with underscores instead of dashes, is used: write "array_first". The dashes of the transformer codes of a generic transformer were replaced by underscores before 5.9, the former name will not be used in v6.0.'],
            $deprecations
        );
        self::assertSame('ABC', $transformer->transform(['abc', 'def'], $this->resolveOptions($transformer, [])));
    }

    /**
     * A transformer code with a dash is used as written when it is registered, without deprecation.
     */
    public function testRegisteredTransformerCodeWithADashIsUsed(): void
    {
        $last = new class implements TransformerInterface {
            public function transform(mixed $value, array $options = []): mixed
            {
                return \is_array($value) ? end($value) : $value;
            }

            public function getCode(): string
            {
                return 'array-first';
            }
        };
        $registry = new TransformerRegistry(new ServiceLocator([
            'array-first' => static fn (): TransformerInterface => $last,
            'array_first' => static fn (): ArrayFirstTransformer => new ArrayFirstTransformer(),
        ]));
        $transformer = new GenericTransformer(new ContextualOptionResolver(), $registry);
        $deprecations = $this->collectDeprecations(static fn () => $transformer->initialize('last', [
            'transformers' => ['array-first' => null],
        ]));

        self::assertSame([], $deprecations);
        self::assertSame('def', $transformer->transform(['abc', 'def'], $this->resolveOptions($transformer, [])));
    }

    /**
     * A contextual option with a dash is given and replaced as written, without deprecation.
     */
    public function testContextualOptionWithADash(): void
    {
        $transformer = $this->createPrefixTransformer(['the-prefix' => ['required' => true]], '{{ the-prefix }}');

        self::assertSame([], $this->collectDeprecations(function () use ($transformer): void {
            self::assertSame('P-abc', $transformer->transform('abc', $this->resolveOptions($transformer, ['the-prefix' => 'P-'])));
        }));
    }

    /**
     * Before 5.9, a contextual option with a dash was named with underscores: its former name, given as an option or
     * in "{{ the_prefix }}", still works, with a deprecation.
     */
    public function testFormerNameOfAContextualOptionIsDeprecated(): void
    {
        $deprecations = $this->collectDeprecations(function () use (&$transformer): void {
            $transformer = $this->createPrefixTransformer(['the-prefix' => ['required' => true]], '{{ the_prefix }}');
        });
        self::assertSame(
            ['Since cleverage/process-bundle 5.10: Using the contextual option "the-prefix" of the generic transformer "prefix" as "{{ the_prefix }}", with underscores instead of dashes, is deprecated: write "{{ the-prefix }}". The dashes of the contextual option codes were replaced by underscores before 5.9, the former name will not be replaced in v6.0.'],
            $deprecations
        );
        self::assertInstanceOf(GenericTransformer::class, $transformer);

        // Given by its code
        self::assertSame([], $this->collectDeprecations(function () use ($transformer): void {
            self::assertSame('P-abc', $transformer->transform('abc', $this->resolveOptions($transformer, ['the-prefix' => 'P-'])));
        }));

        // Given by its former name
        self::assertSame(
            ['Since cleverage/process-bundle 5.10: Using the contextual option "the-prefix" of the generic transformer "prefix" by the name "the_prefix", with underscores instead of dashes, is deprecated: use "the-prefix". The dashes of the contextual option codes were replaced by underscores before 5.9, the former name will not be accepted in v6.0.'],
            $this->collectDeprecations(function () use ($transformer): void {
                self::assertSame('P-abc', $transformer->transform('abc', $this->resolveOptions($transformer, ['the_prefix' => 'P-'])));
            })
        );

        // Required
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "the-prefix" is missing.');
        $this->resolveOptions($transformer, []);
    }

    /**
     * The default value of a contextual option with a dash, given by its former name, is used when it is not given.
     */
    public function testFormerNameOfAnOptionalContextualOption(): void
    {
        $transformer = $this->createPrefixTransformer(['the-prefix' => ['default' => 'D-']], '{{ the-prefix }}');
        self::assertSame('D-abc', $transformer->transform('abc', $this->resolveOptions($transformer, [])));
        $this->collectDeprecations(function () use ($transformer): void {
            self::assertSame('P-abc', $transformer->transform('abc', $this->resolveOptions($transformer, ['the_prefix' => 'P-'])));
        });

        $transformer = $this->createPrefixTransformer(['the-prefix' => ['required' => false]], '{{ the-prefix }}');
        self::assertSame('abc', $transformer->transform('abc', $this->resolveOptions($transformer, [])));
    }

    /**
     * When both the code with a dash and its former name are contextual options, none is deprecated.
     */
    public function testContextualOptionsWithADashAndAnUnderscore(): void
    {
        $deprecations = $this->collectDeprecations(function () use (&$transformer): void {
            $transformer = $this->createPrefixTransformer([
                'the-prefix' => ['default' => 'D-'],
                'the_prefix' => ['default' => 'U-'],
            ], '{{ the-prefix }}{{ the_prefix }}');
        });
        self::assertSame([], $deprecations);
        self::assertInstanceOf(GenericTransformer::class, $transformer);
        self::assertSame('D-U-abc', $transformer->transform('abc', $this->resolveOptions($transformer, [])));
    }

    /**
     * Generic transformer prefixing the value with the given contextual option.
     *
     * @param array<string, array<string, mixed>> $contextualOptions
     */
    private function createPrefixTransformer(array $contextualOptions, string $prefix): GenericTransformer
    {
        $transformer = new GenericTransformer(new ContextualOptionResolver(), $this->createRegistry());
        $transformer->initialize('prefix', [
            'contextual_options' => $contextualOptions,
            'transformers' => [
                'callback' => ['callback' => 'sprintf', 'left_parameters' => [$prefix.'%s']],
            ],
        ]);

        return $transformer;
    }

    private function createRegistry(): TransformerRegistry
    {
        return new TransformerRegistry(new ServiceLocator([
            'callback' => static fn (): CallbackTransformer => new CallbackTransformer(),
            'array_first' => static fn (): ArrayFirstTransformer => new ArrayFirstTransformer(),
        ]));
    }

    /**
     * @return list<string>
     */
    private function collectDeprecations(callable $callable): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    /**
     * Generic transformer applying substr($value, {{ offset }}, {{ length }}).
     *
     * @param array<string, array<string, mixed>> $contextualOptions
     */
    private function createSubstrTransformer(array $contextualOptions): GenericTransformer
    {
        $registry = new TransformerRegistry(new ServiceLocator([
            'callback' => static fn (): CallbackTransformer => new CallbackTransformer(),
        ]));

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
