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

use CleverAge\ProcessBundle\Exception\TransformerException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Read a property from the input value and return it
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class RecursivePropertySetterTransformer implements ConfigurableTransformerInterface
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
     * @throws NoSuchPropertyException
     * @throws TransformerException
     * @throws InvalidArgumentException
     * @throws AccessException
     * @throws UnexpectedTypeException
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        if (null === $value && $options['ignore_null']) {
            return null;
        }

        if ($options['ignore_missing'] && !$this->accessor->isReadable($value, $options['iterator'])) {
            return null;
        }

        $iterable = $this->accessor->getValue($value, $options['iterator']);
        if (!is_iterable($iterable)) {
            throw new TransformerException($options['iterator']);
        }

        $protertiesToSet = [];
        foreach ($options['set_properties'] as $propertyName => $propertyValuePath) {
            $protertiesValue = null;
            if ($options['ignore_missing'] && !$this->accessor->isReadable($value, $propertyValuePath)) {
                $protertiesValue = null;
            } else {
                $protertiesValue = $this->accessor->getValue($value, $propertyValuePath);
                if (null === $protertiesValue && !$options['ignore_null']) {
                    throw new TransformerException($propertyValuePath);
                }
            }
            $protertiesToSet[$propertyName] = $protertiesValue;
        }

        foreach ($iterable as &$item) {
            foreach ($protertiesToSet as $protertyName => $propertyValue) {
                try {
                    $this->accessor->setValue($item, $protertyName, $propertyValue);
                } catch (NoSuchPropertyException $e) {
                    if ($item instanceof \stdClass) {
                        $item = (object) array_merge((array) $item, [$protertyName => $propertyValue]);
                    } else {
                        throw $e;
                    }
                }
            }
        }

        return $iterable;
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'recursive_property_setter';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'iterator',
                'set_properties',
            ]
        );

        $resolver->setDefaults(
            [
                'ignore_null' => false,
                'ignore_missing' => false,
            ]
        );

        $resolver->setAllowedTypes('iterator', ['string']);
        $resolver->setAllowedTypes('set_properties', ['array']);
        $resolver->setAllowedTypes('ignore_null', ['boolean']);
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
    }
}
