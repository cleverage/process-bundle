<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
class ArrayMapTransformer implements ConfigurableTransformerInterface, TransformerRegistryAwareInterface
{
    /** @var TransformerRegistry */
    protected $transformerRegistry;

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
        if (!is_array($values) && $values instanceof \Traversable) {
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
        $resolver->setDefaults([
            'skip_null' => false,
        ]);
        $resolver->setAllowedTypes('skip_null', ['bool']);
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
                            null === $transformerOptions ? [] : $transformerOptions
                        );
                    }
                }

                return $transformers;
            }
        );
    }

    /**
     * @param TransformerRegistry $transformerRegistry
     */
    public function setTransformerRegistry(TransformerRegistry $transformerRegistry)
    {
        $this->transformerRegistry = $transformerRegistry;
    }
}
