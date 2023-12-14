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
 * Unset a key from an array.
 */
class ArrayUnsetTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed
    {
        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Given value is not an array');
        }
        unset($value[$options['key']]);

        return $value;
    }

    public function getCode(): string
    {
        return 'array_unset';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('key');
        $resolver->setAllowedTypes('key', ['string', 'int']);
    }
}
