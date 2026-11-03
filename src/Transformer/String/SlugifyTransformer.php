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

use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use CleverAge\ProcessBundle\Transformer\Regex;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Slugify a value.
 *
 * @final since 5.4
 */
class SlugifyTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): string
    {
        if (\is_int($value) || \is_float($value) || \is_bool($value) || $value instanceof \Stringable) {
            $value = (string) $value;
        } elseif (!\is_string($value)) {
            throw new UnexpectedValueException(\sprintf('The slugify transformer expects a string, %s given.', get_debug_type($value)));
        }
        /** @var \Transliterator $transliterator */
        $transliterator = $options['transliterator'];
        $string = $transliterator->transliterate($value);
        if (false === $string) {
            throw new UnexpectedValueException("Unable to transliterate the value: {$transliterator->getErrorMessage()}");
        }

        if ($options['strip_tags']) {
            $string = strip_tags($string);
        }

        $slug = preg_replace(
            $options['replace'],
            (string) $options['separator'],
            // The characters left by the transliterator may be multibyte
            mb_strtolower(trim($string))
        );
        if (null === $slug) {
            // A PCRE error gave an empty slug
            Regex::checkLastError($options['replace']);
        }

        return trim((string) $slug, $options['separator']);
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'slugify';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'transliterator' => 'NFD; [:Nonspacing Mark:] Remove; NFC',
                'replace' => '/[^a-z0-9]+/',
                'separator' => '_',
                // strip_tags() removes everything after a "<" (e.g. "Tom<Jerry" gives "tom")
                'strip_tags' => true,
            ]
        );
        $resolver->setAllowedTypes('strip_tags', ['bool']);
        $resolver->setAllowedTypes('transliterator', ['string']);
        $resolver->setAllowedTypes('replace', ['string']);
        $resolver->setAllowedTypes('separator', ['string']);
        $resolver->setNormalizer('replace', static fn (Options $options, string $pattern): string => Regex::validate($pattern, 'replace'));

        $resolver->setNormalizer(
            'transliterator',
            static function (Options $options, $value): \Transliterator {
                $transliterator = \Transliterator::create($value);
                if (!$transliterator instanceof \Transliterator) {
                    throw new InvalidOptionsException(\sprintf('Invalid "transliterator" option: %s', intl_get_error_message()));
                }

                return $transliterator;
            }
        );
    }
}
