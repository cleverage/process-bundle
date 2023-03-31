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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize the given value based on options
 */
class DenormalizeTransformer implements ConfigurableTransformerInterface
{
    public function __construct(
        protected DenormalizerInterface $denormalizer
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['class']);
        $resolver->setAllowedTypes('class', ['string']);
        $resolver->setDefaults([
            'format' => null,
            'context' => [],
        ]);
        $resolver->setAllowedTypes('format', ['null', 'string']);
        $resolver->setAllowedTypes('context', ['array']);
    }


    public function transform(mixed $value, array $options = []): mixed
    {
        return $this->denormalizer->denormalize($value, $options['class'], $options['format'], $options['context']);
    }

    /**
     * Returns the unique code to identify the transformer
     */
    public function getCode(): string
    {
        return 'denormalize';
    }
}
