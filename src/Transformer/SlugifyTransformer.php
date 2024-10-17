<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Slugify a value.
 */
class SlugifyTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): string
    {
        /** @var \Transliterator $transliterator */
        $transliterator = $options['transliterator'];
        $string = $transliterator->transliterate($value);

        return trim(
            (string) preg_replace(
                $options['replace'],
                (string) $options['separator'],
                strtolower(trim(strip_tags($string)))
            ),
            $options['separator']
        );
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
            ]
        );

        $resolver->setNormalizer(
            'transliterator',
            static fn (Options $options, $value): ?\Transliterator => \Transliterator::create($value)
        );
    }
}
