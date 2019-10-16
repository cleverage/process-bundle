<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A generic class that can be used to create configuration-driven transformer instances
 */
class GenericTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    /** @var string */
    protected $transformerCode;

    /** @var array */
    protected $preconfiguredTransformerOptions;

    /** @var array */
    protected $contextualOptions;

    /** @var ContextualOptionResolver */
    protected $contextualOptionResolver;

    /**
     * GenericTransformer constructor.
     *
     * @param ContextualOptionResolver $contextualOptionResolver
     * @param TransformerRegistry      $transformerRegistry
     */
    public function __construct(ContextualOptionResolver $contextualOptionResolver, TransformerRegistry $transformerRegistry)
    {
        $this->contextualOptionResolver = $contextualOptionResolver;
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * Register the generic options, and load the transformer list
     *
     * @param string $code
     * @param array  $options
     */
    public function initialize(string $code, array $options = [])
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
     *
     * @param OptionsResolver $resolver
     */
    public function configureInitialOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('contextual_options', []);
        $resolver->setAllowedTypes('contextual_options', 'array');
        $resolver->setNormalizer('contextual_options', function (Options $options, $value) {
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
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
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
        $resolver->setNormalizer('transformers', function (Options $options, $transformerOptions) {
            if ($transformerOptions !== []) {
                throw new \InvalidArgumentException('Transformers option should not be used at this point');
            }

            $transformerOptions = $this->normalizeTransformerOptions($options, $this->preconfiguredTransformerOptions);
            $transformers = $this->normalizeTransformers($options, $transformerOptions);

            return $transformers;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value, array $options = [])
    {
        return $this->applyTransformers($options['transformers'], $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return $this->transformerCode;
    }

    /**
     * Get the real transformer from contextual options + generic definitions
     *
     * @param Options $options
     * @param array   $transformerOptions
     *
     * @return array
     */
    public function normalizeTransformerOptions(Options $options, $transformerOptions)
    {
        $contextualizedOptionValues = [];
        foreach ($this->contextualOptions as $contextualOption => $contextualOptionConfig) {
            $contextualizedOptionValues[$contextualOption] = $options[$contextualOption];
        }

        return $this->contextualOptionResolver->contextualizeOptions($transformerOptions, $contextualizedOptionValues);
    }

    /**
     * Available options for contextual_options
     *
     * @param OptionsResolver $resolver
     */
    public function configureContextualOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('required', true);
        $resolver->setAllowedTypes('required', 'bool');

        $resolver->setDefault('default', null);

        $resolver->setDefault('default_is_null', false);
        $resolver->setAllowedTypes('default_is_null', 'bool');
    }

}
