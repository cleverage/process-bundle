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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Return always the same value configured in options, redundant when used inside mapping except in certain useful
 * circumstances.
 */
class ConstantTransformer implements ConfigurableTransformerInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['constant']);
    }

    /**
     * Must return the transformed $value.
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        return $options['constant'] ?? null;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'constant';
    }
}
