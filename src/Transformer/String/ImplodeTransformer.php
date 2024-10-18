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

namespace CleverAge\ProcessBundle\Transformer\String;

use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Implode multiple array values to a string, based on a split character.
 */
class ImplodeTransformer implements ConfigurableTransformerInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('separator');
        $resolver->setDefault('separator', '|');
        $resolver->setAllowedTypes('separator', 'string');
    }

    public function transform(mixed $value, array $options = []): string
    {
        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Given value is not an array');
        }

        return implode($options['separator'], $value);
    }

    public function getCode(): string
    {
        return 'implode';
    }
}
