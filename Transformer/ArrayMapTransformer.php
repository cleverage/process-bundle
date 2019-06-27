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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Applies transformers to each element of an array
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ArrayMapTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

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
     * @throws \UnexpectedValueException
     *
     * @return mixed $value
     */
    public function transform($values, array $options = [])
    {
        if (!\is_array($values) && !$values instanceof \Traversable) {
            throw new \UnexpectedValueException('Input value must be an array or traversable');
        }

        $results = [];
        /** @noinspection ForeachSourceInspection */
        foreach ($values as $key => $item) {
            $item = $this->applyTransformers($options['transformers'], $item);
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
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureTransformersOptions($resolver);
        $resolver->setRequired(
            [
                'transformers',
            ]
        );
        $resolver->setDefaults(
            [
                'skip_null' => false,
            ]
        );
        $resolver->setAllowedTypes('skip_null', ['boolean']);
    }
}
