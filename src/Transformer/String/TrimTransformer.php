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
 * Trim an input based on specific characters.
 *
 * @final since 5.4
 */
class TrimTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     */
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

        $charlist = $options['charlist'];
        // trim() removes bytes: a multibyte character of the list would cut the characters sharing its bytes (ranges
        // with ".." are only supported for single-byte characters)
        if (1 === preg_match('/[\x80-\xff]/', $charlist)) {
            return mb_trim((string) $value, $charlist);
        }

        return trim((string) $value, $charlist);
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
