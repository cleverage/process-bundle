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
 * Cast a value to a different PHP type.
 */
class CastTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed
    {
        settype($value, $options['type']);

        return $value;
    }

    public function getCode(): string
    {
        return 'cast';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['type']);
        $resolver->setAllowedTypes('type', ['string']);
    }
}
