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

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Applies transformers to each element of an array
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ArrayMapTransformer implements ConfigurableTransformerInterface
{
    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /**
     * @param TransformerRegistry $transformerRegistry
     */
    public function __construct(TransformerRegistry $transformerRegistry)
    {
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * Must return the transformed $value
     *
     * @param array $values
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     *
     * @return mixed $value
     */
    public function transform($values, array $options = [])
    {
        if (!\is_array($values) && !$values instanceof \Traversable) {
            throw new \UnexpectedValueException('Input value must be an array or traversable');
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $options = $resolver->resolve($options);
        /** @var array $transformers */
        $transformers = $options['transformers'];

        $results = [];
        /** @noinspection ForeachSourceInspection */
        foreach ($values as $key => $item) {
            foreach ($transformers as $transformerCode => $transformerOptions) {
                if (null === $transformerOptions) {
                    $transformerOptions = [];
                }
                $transformer = $this->transformerRegistry->getTransformer($transformerCode);
                $item = $transformer->transform($item, $transformerOptions);
            }
            if (null === $item && $options['skip_null']) {
                continue;
            }
            $results[$key] = $item;
        }

        return $results;
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'array_map';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'transformers',
            ]
        );
        $resolver->setAllowedTypes('transformers', ['array']);
        $resolver->setDefaults(
            [
                'skip_null' => false,
            ]
        );
        $resolver->setAllowedTypes('skip_null', ['boolean']);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'transformers',
            function (Options $options, $transformers) {
                /** @var array $transformers */
                foreach ($transformers as $transformerCode => &$transformerOptions) {
                    $transformerOptionsResolver = new OptionsResolver();
                    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */// @todo remove me sometimes
                    $transformer = $this->transformerRegistry->getTransformer($transformerCode);
                    if ($transformer instanceof ConfigurableTransformerInterface) {
                        $transformer->configureOptions($transformerOptionsResolver);
                        $transformerOptions = $transformerOptionsResolver->resolve(
                            $transformerOptions ?? []
                        );
                    }
                }

                return $transformers;
            }
        );
    }
}
