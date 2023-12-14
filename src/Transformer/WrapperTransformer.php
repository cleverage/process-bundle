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

class WrapperTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value.
     */
    public function transform(mixed $value, array $options = []): array
    {
        return [
            $options['wrapper_key'] => $value,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'wrapper_key' => 0,
        ]);
        $resolver->setAllowedTypes('wrapper_key', ['string', 'int']);
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'wrapper';
    }
}
