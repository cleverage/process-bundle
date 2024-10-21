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

use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Read a property from the input value and return it.
 */
class PropertyAccessorTransformer implements ConfigurableTransformerInterface
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

        if ($options['ignore_missing'] && !$this->accessor->isReadable($value, $options['property_path'])) {
            return null;
        }

        return $this->accessor->getValue($value, $options['property_path']);
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'property_accessor';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['property_path']);

        $resolver->setDefaults([
            'ignore_null' => false,
            'ignore_missing' => false,
        ]);

        $resolver->setAllowedTypes('property_path', ['string']);
        $resolver->setAllowedTypes('ignore_null', ['boolean']);
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
    }
}
