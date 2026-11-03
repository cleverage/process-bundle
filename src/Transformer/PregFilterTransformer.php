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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @final since 5.4
 */
class PregFilterTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<mixed>|string|null
     */
    // @phpstan-ignore return.unusedType (narrowing the return type of a non-final class breaks the child classes, see #279)
    public function transform(mixed $value, array $options = []): array|string|null
    {
        $pattern = $options['pattern'];
        $replacement = $options['replacement'];

        $result = preg_filter($pattern, $replacement, (string) $value);
        // null is also "no match": a PCRE error (e.g. invalid UTF-8 with the "u" modifier) is not
        if (null === $result) {
            Regex::checkLastError(\is_array($pattern) ? implode('", "', $pattern) : $pattern);
        }

        return $result;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'preg_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['pattern', 'replacement']);
        $resolver->setAllowedTypes('pattern', ['string', 'array']);
        $resolver->setAllowedTypes('replacement', ['string', 'array']);
        $resolver->setNormalizer(
            'pattern',
            static function (Options $options, string|array $value): string|array {
                foreach ((array) $value as $pattern) {
                    if (!\is_string($pattern)) {
                        throw new InvalidOptionsException(\sprintf('The patterns of the "pattern" option must be strings, %s given.', get_debug_type($pattern)));
                    }
                    Regex::validate($pattern);
                }

                return $value;
            }
        );
        $resolver->setNormalizer(
            'replacement',
            static function (Options $options, string|array $value): string|array {
                if (\is_array($value) && !\is_array($options['pattern'])) {
                    throw new InvalidOptionsException('The "replacement" option can only be an array when the "pattern" option is an array');
                }

                return $value;
            }
        );
    }
}
