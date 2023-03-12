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
 * Return the first element of an array
 */
class ArrayFirstTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function transform($value, array $options = [])
    {
        if ($options['allow_not_iterable'] && ! is_iterable($value)) {
            return $value;
        }

        return reset($value);
    }

    /**
     * Returns the unique code to identify the transformer
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
    }
}
