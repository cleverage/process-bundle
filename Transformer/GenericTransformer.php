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
        // TODO define normalizer

        $resolver->setDefault('transformers', []);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        foreach ($this->contextualOptions as $option => $optionConfig) {
            // TODO allow more complex usage
            $resolver->setDefault($option, null);
        }

        // TODO we use the transformers option for internal processing here... but it's also accessible through config
        $this->configureTransformersOptions($resolver);
        $resolver->setNormalizer('transformers', function (Options $options, $transformerOptions) {
            if ($transformerOptions !== []) {
                throw new \InvalidArgumentException('Transformers option should not be used');
            }

            $transformerOptions = $this->normalizeTransformerOptions($options, $this->preconfiguredTransformerOptions);
            $transformers = $this->normalizeTransformers($options, $transformerOptions);

            return $transformers;
        });
    }

    public function transform($value, array $options = [])
    {
        return $this->applyTransformers($options['transformers'], $value);
    }

    public function getCode()
    {
        return $this->transformerCode;
    }

    public function normalizeTransformerOptions(Options $options, $transformerOptions)
    {
        $contextualizedOptionValues = [];
        foreach ($this->contextualOptions as $contextualOption => $contextualOptionConfig) {
            $contextualizedOptionValues[$contextualOption] = $options[$contextualOption];
        }

        return $this->contextualOptionResolver->contextualizeOptions($transformerOptions, $contextualizedOptionValues);
    }

}
