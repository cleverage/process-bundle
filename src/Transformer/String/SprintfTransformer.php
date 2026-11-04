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
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Use sprintf() function to format string.
 *
 * @final since 5.4
 */
class SprintfTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): string
    {
        if (!\is_array($value)) {
            $value = [$value];
        }

        return vsprintf($options['format'], $value);
    }

    public function getCode(): string
    {
        return 'sprintf';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('format', '%s');
        $resolver->setAllowedTypes('format', 'string');
        // An invalid format (e.g. a "%" alone) is rejected with the configuration, not with the first value
        $resolver->setAllowedValues('format', static function (string $format): bool {
            // Enough arguments for every conversion specification, positional ones (and positional widths or precisions,
            // "%1\$*2\$d") included; integers, as a width or a precision given as an argument ("%*d", "%.*f") must be one
            preg_match_all('/[%*](\d+)\$/', $format, $positions);
            $count = max([substr_count($format, '%') + substr_count($format, '*'), ...array_map(intval(...), $positions[1])]) + 1;
            try {
                vsprintf($format, array_fill(0, $count, 0));
            } catch (\ValueError $error) {
                throw new InvalidOptionsException(\sprintf('Invalid "format" option "%s": %s', $format, $error->getMessage()), 0, $error);
            }

            return true;
        });
    }
}
