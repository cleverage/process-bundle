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

namespace Transformer;

namespace CleverAge\ProcessBundle\Transformer\String;

use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Trim an input based on specific characters.
 */
class TrimTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, ?array $options = []): ?string
    {
        if (null === $options || [] === $options) {
            $options = [
                'charlist' => " \t\n\r\0\x0B",
            ];
        }

        if (null === $value) {
            return null;
        }

        return trim((string) $value, $options['charlist']);
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'trim';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'charlist' => " \t\n\r\0\x0B",
        ]);
        $resolver->setAllowedTypes('charlist', ['string']);
    }
}
