<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Read a property from the input value and return it
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class PropertyAccessorTransformer implements ConfigurableTransformerInterface
{
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
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException
     * @throws \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $options = $resolver->resolve($options);

        if (null === $value && $options['ignore_null']) {
            return null;
        }

        if ($options['ignore_missing'] && !$this->accessor->isReadable($value, $options['property_path'])) {
            return null;
        }

        return $this->accessor->getValue($value, $options['property_path']);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'property_accessor';
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
                'property_path',
            ]
        );

        $resolver->setDefaults(
            [
                'ignore_null' => false,
                'ignore_missing' => false,
            ]
        );

        $resolver->setAllowedTypes('property_path', ['string']);
        $resolver->setAllowedTypes('ignore_null', ['boolean']);
    }
}
