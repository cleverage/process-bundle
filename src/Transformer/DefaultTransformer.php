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

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Return a default value when the input is falsy, otherwise return the input unchanged.
 */
class DefaultTransformer implements ConfigurableTransformerInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('value');
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if (!$value) {
            return $options['value'];
        }

        return $value;
    }

    public function getCode(): string
    {
        return 'default';
    }
}
