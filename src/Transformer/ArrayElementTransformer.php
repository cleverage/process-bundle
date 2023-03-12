<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Return the nth element of an array
 */
class ArrayElementTransformer implements ConfigurableTransformerInterface
{
    public function transform($value, array $options = [])
    {
        return array_values(array_slice($value, $options['index'], 1))[0];
    }

    public function getCode(): string
    {
        return 'array_element';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['index']);
        $resolver->setAllowedTypes('index', ['integer']);
    }
}
