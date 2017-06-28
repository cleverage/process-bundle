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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Maps properties of an array/object to an other array/object
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class MappingTransformer implements ConfigurableTransformerInterface, TransformerRegistryAwareInterface
{
    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * Must return the transformed $value
     *
     * @param mixed $input
     * @param array $options
     *
     * @throws \Exception
     *
     * @return mixed $value
     */
    public function transform($input, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $result = $options['initial_value'];
        /** @noinspection ForeachSourceInspection */
        foreach ($options['mapping'] as $targetProperty => $mapping) {
            if (null !== $mapping['constant']) {
                $transformedValue = $mapping['constant'];
            } elseif ($mapping['set_null']) {
                $transformedValue = null;
            } else {
                $sourceProperty = $mapping['code'] === null ? $targetProperty : $mapping['code'];
                if (is_array($sourceProperty)) {
                    $transformedValue = [];
                    /** @var array $sourceProperty */
                    foreach ($sourceProperty as $destKey => $srcKey) {
                        if (!array_key_exists($srcKey, $input)) {
                            if (!$mapping['ignore_missing'] && !$options['ignore_missing']) {
                                throw new \RuntimeException("Missing property {$srcKey}");
                            }
                            continue;
                        }
                        $transformedValue[$destKey] = $input[$srcKey];
                    }

                } else {
                    if (!array_key_exists($sourceProperty, $input)) {
                        if (!$mapping['ignore_missing'] && !$options['ignore_missing']) {
                            throw new \RuntimeException("Missing property {$sourceProperty}");
                        }
                        continue;
                    }
                    $transformedValue = $input[$sourceProperty];
                }
            }

            /** @noinspection ForeachSourceInspection */
            foreach ($mapping['transformers'] as $transformerCode => $transformerOptions) {
                $transformer = $this->transformerRegistry->getTransformer($transformerCode);
                $transformedValue = $transformer->transform(
                    $transformedValue,
                    $transformerOptions ?: []
                );
            }

            if (is_array($result)) {
                $result[$targetProperty] = $transformedValue;
            } else {
                $this->accessor->setValue($result, $targetProperty, $transformedValue);
            }
        }

        return $result;
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
                'mapping',
            ]
        );
        $resolver->setAllowedTypes('mapping', ['array']);
        $resolver->setDefaults(
            [
                'ignore_missing' => false,
                'ignore_extra' => false,
                'initial_value' => [],
            ]
        );
        $resolver->setAllowedTypes('ignore_missing', ['bool']);
        $resolver->setAllowedTypes('ignore_extra', ['bool']);

        $resolver->setNormalizer(
            'mapping',
            function (Options $options, $value) {
                $resolvedMapping = [];
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);
                /** @var array $value */
                foreach ($value as $property => $mappingConfig) {
                    $resolvedMapping[$property] = $mappingResolver->resolve(
                        null === $mappingConfig ? [] : $mappingConfig
                    );
                }

                return $resolvedMapping;
            }
        );
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'mapping';
    }

    /**
     * @param TransformerRegistry $transformerRegistry
     */
    public function setTransformerRegistry(TransformerRegistry $transformerRegistry)
    {
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function configureMappingOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'code' => null, // Source property
                'constant' => null,
                'set_null' => false, // Because the "null" value cannot be covered by the constant option
                'ignore_missing' => false,
                'transformers' => [],
            ]
        );
        $resolver->setAllowedTypes('code', ['NULL', 'string', 'array']);
        $resolver->setAllowedTypes('set_null', ['boolean']);
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
        $resolver->setAllowedTypes('transformers', ['array']);
        $resolver->setNormalizer( // This logic is duplicated from the array_map transformer @todo fix me
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
}
