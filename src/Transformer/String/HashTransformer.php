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
 * Use hash() function to generate hash value.
 */
class HashTransformer implements ConfigurableTransformerInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('algo');
        $resolver->setAllowedValues('algo', hash_algos());
        $resolver->setAllowedTypes('algo', 'string');

        $resolver->setDefined('raw_output');
        $resolver->setDefault('raw_output', false);
    }

    public function transform(mixed $value, array $options = []): string
    {
        return hash((string) $options['algo'], (string) $value, $options['raw_output']);
    }

    public function getCode(): string
    {
        return 'hash';
    }
}
