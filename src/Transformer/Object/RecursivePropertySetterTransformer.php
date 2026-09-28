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

namespace CleverAge\ProcessBundle\Transformer\Object;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Read an iterable from the input, then set one or more properties on each of its items, using values read from the
 * input itself.
 */
class RecursivePropertySetterTransformer implements ConfigurableTransformerInterface
{
    public function __construct(
        protected PropertyAccessorInterface $accessor,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
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

        $propertiesToSet = [];
        foreach ($options['set_properties'] as $propertyName => $propertyValuePath) {
            $propertyValue = null;
            if (!$options['ignore_missing'] || $this->accessor->isReadable($value, $propertyValuePath)) {
                $propertyValue = $this->accessor->getValue($value, $propertyValuePath);
                if (null === $propertyValue && !$options['ignore_null']) {
                    throw new TransformerException($propertyValuePath);
                }
            }
            $propertiesToSet[$propertyName] = $propertyValue;
        }

        foreach ($iterable as &$item) {
            foreach ($propertiesToSet as $propertyPath => $propertyValue) {
                try {
                    $this->accessor->setValue($item, $propertyPath, $propertyValue);
                } catch (NoSuchPropertyException $e) {
                    if ($item instanceof \stdClass) {
                        $item = (object) array_merge((array) $item, [
                            $propertyPath => $propertyValue,
                        ]);
                    } else {
                        throw $e;
                    }
                }
            }
        }

        return $iterable;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'recursive_property_setter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['iterator', 'set_properties']);

        $resolver->setDefaults([
            'ignore_null' => false,
            'ignore_missing' => false,
        ]);

        $resolver->setAllowedTypes('iterator', ['string']);
        $resolver->setAllowedTypes('set_properties', ['array']);
        $resolver->setAllowedTypes('ignore_null', ['boolean']);
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
    }
}
