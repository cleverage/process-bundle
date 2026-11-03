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

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Quickly replace a list of values in a string.
 *
 * ##### Options
 *
 * * `replace_mapping` (`array`, _required_): a list of _pattern_ => _replacement_ to apply on input strings
 *
 * @final since 5.4
 */
class MultiReplaceTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        foreach ($options['replace_mapping'] as $pattern => $replacement) {
            // A numeric key of the mapping is an int
            $value = str_replace((string) $pattern, (string) $replacement, (string) $value);
        }

        return $value;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('replace_mapping');
        $resolver->setAllowedTypes('replace_mapping', 'array');
        $resolver->setAllowedValues(
            'replace_mapping',
            static function (array $mapping): bool {
                foreach ($mapping as $replacement) {
                    if (null !== $replacement && !\is_scalar($replacement)) {
                        throw new InvalidOptionsException(\sprintf('The replacements of the "replace_mapping" option must be strings, %s given.', get_debug_type($replacement)));
                    }
                }

                return true;
            }
        );
    }

    public function getCode(): string
    {
        return 'multi_replace';
    }
}
