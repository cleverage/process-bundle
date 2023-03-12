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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize the given value based on options
 */
class NormalizeTransformer implements ConfigurableTransformerInterface
{
    public function __construct(
        protected NormalizerInterface $normalizer
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'format' => null,
            'context' => [],
        ]);
        $resolver->setAllowedTypes('format', ['null', 'string']);
        $resolver->setAllowedTypes('context', ['array']);
    }

    /**
     * @param mixed $value
     *
     * @return array|bool|float|int|mixed|string
     */
    public function transform($value, array $options = [])
    {
        return $this->normalizer->normalize($value, $options['format'], $options['context']);
    }

    /**
     * Returns the unique code to identify the transformer
     */
    public function getCode(): string
    {
        return 'normalize';
    }
}
