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
 * Perform a regular expression match.
 */
class PregMatchTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): ?array
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($options['mode_all']) {
            preg_match_all($options['pattern'], (string) $value, $matches, $options['flags'], $options['offset']);
        } else {
            preg_match($options['pattern'], (string) $value, $matches, $options['flags'], $options['offset']);
        }

        return $matches;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'preg_match';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['pattern']);
        $resolver->setAllowedTypes('pattern', ['string']);
        $resolver->setDefault('flags', 0);
        $resolver->setAllowedTypes('flags', ['int']);
        $resolver->setDefault('offset', 0);
        $resolver->setAllowedTypes('offset', ['int']);
        $resolver->setDefault('mode_all', false);
        $resolver->setAllowedTypes('mode_all', ['boolean']);
    }
}
