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

class PregFilterTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): array|string|null
    {
        $pattern = $options['pattern'];
        $replacement = $options['replacement'];

        return preg_filter($pattern, (string) $replacement, (string) $value);
    }

    /**
     * Returns the unique code to identify the transformer
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
    }
}
