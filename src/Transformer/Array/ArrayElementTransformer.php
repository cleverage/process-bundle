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

namespace CleverAge\ProcessBundle\Transformer\Array;

use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Return the nth element of an array.
 *
 * @final since 5.4
 */
class ArrayElementTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        $index = $options['index'];
        // null when the index is outside the array, at either end: no "Undefined array key" warning after the end, and
        // not the first element (as array_slice() would give) before the start
        if ($index >= \count($value) || $index < -\count($value)) {
            return null;
        }

        return array_values(\array_slice($value, $index, 1))[0];
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
