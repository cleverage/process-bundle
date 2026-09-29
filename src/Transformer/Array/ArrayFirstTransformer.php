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
 * Return the first element of an array (or of any iterable).
 *
 * A non-iterable input throws an exception, unless the "allow_not_iterable" option is true: it is then returned
 * unchanged.
 */
class ArrayFirstTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value.
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        if (!is_iterable($value)) {
            if ($options['allow_not_iterable']) {
                return $value;
            }

            throw new \UnexpectedValueException(\sprintf('Given value is not iterable (%s), set the "allow_not_iterable" option to true to return it unchanged', get_debug_type($value)));
        }

        if (\is_array($value)) {
            return reset($value);
        }

        foreach ($value as $item) {
            return $item;
        }

        return false;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'array_first';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_not_iterable' => false,
        ]);
        $resolver->setAllowedTypes('allow_not_iterable', ['bool']);
    }
}
