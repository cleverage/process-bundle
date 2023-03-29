<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A generic class that can be used to create configuration-driven transformer instances
 */
class GenericTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    protected ?string $transformerCode = null;

    protected ?array $preconfiguredTransformerOptions = null;

    protected ?array $contextualOptions = null;

    public function __construct(
        protected ContextualOptionResolver $contextualOptionResolver,
        TransformerRegistry $transformerRegistry
    ) {
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * Register the generic options, and load the transformer list
     */
    public function initialize(string $code, array $options = []): void
    {
        $this->transformerCode = $code;
        $resolver = new OptionsResolver();
        $this->configureInitialOptions($resolver);

        $initialOptions = $resolver->resolve($options);
        $this->contextualOptions = $initialOptions['contextual_options'];
        $this->preconfiguredTransformerOptions = $initialOptions['transformers'];
    }

    /**
     * Called on instance creation
     */
    public function configureInitialOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('contextual_options', []);
        $resolver->setAllowedTypes('contextual_options', 'array');
        $resolver->setNormalizer('contextual_options', function (Options $options, $value): array {
            $configuration = [];
            foreach ($value as $optionCode => $optionConfig) {
                $resolver = new OptionsResolver();
                $this->configureContextualOptions($resolver);
                $configuration[$optionCode] = $resolver->resolve($optionConfig ?? []);
            }

            return $configuration;
        });

        $resolver->setDefault('transformers', []);
    }

    /**
     * Called on process startup, prepare the real transformers
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        foreach ($this->contextualOptions as $option => $optionConfig) {
            if ($optionConfig['default'] !== null || $optionConfig['default_is_null']) {
                $resolver->setDefault($option, $optionConfig['default']);
            }

            if ($optionConfig['required']) {
                $resolver->setRequired($option);
            }
        }

        // Get the transformer list + apply transformer option resolution by context
        $this->configureTransformersOptions($resolver);
        $resolver->setNormalizer('transformers', function (Options $options, $transformerOptions): array {
            if ($transformerOptions !== []) {
                throw new InvalidArgumentException('Transformers option should not be used at this point');
            }

            $transformerOptions = $this->normalizeTransformerOptions($options, $this->preconfiguredTransformerOptions);

            return $this->normalizeTransformers($options, $transformerOptions);
        });
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        return $this->applyTransformers($options['transformers'], $value);
    }

    public function getCode(): string
    {
        return $this->transformerCode;
    }

    /**
     * Get the real transformer from contextual options + generic definitions
     */
    public function normalizeTransformerOptions(Options $options, array $transformerOptions): array
    {
        $contextualizedOptionValues = [];
        foreach ($this->contextualOptions as $contextualOption => $contextualOptionConfig) {
            $contextualizedOptionValues[$contextualOption] = $options[$contextualOption];
        }

        return $this->contextualOptionResolver->contextualizeOptions($transformerOptions, $contextualizedOptionValues);
    }

    /**
     * Available options for contextual_options
     */
    public function configureContextualOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('required', true);
        $resolver->setAllowedTypes('required', 'bool');

        $resolver->setDefault('default', null);

        $resolver->setDefault('default_is_null', false);
        $resolver->setAllowedTypes('default_is_null', 'bool');
    }
}
