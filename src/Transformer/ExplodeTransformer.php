<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Explode a string to an array based on a split character.
 */
class ExplodeTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): array
    {
        if (null === $value || '' === $value) {
            return [];
        }

        return explode($options['delimiter'], (string) $value);
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'explode';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['delimiter']);
        $resolver->setAllowedTypes('delimiter', ['string']);
    }
}
