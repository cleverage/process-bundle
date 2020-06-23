<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Unset a key from an array
 */
class ArrayUnsetTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $options = [])
    {
        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Given value is not an array');
        }
        unset($value[$options['key']]);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode(): string
    {
        return 'array_unset';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('key');
        $resolver->setAllowedTypes('key', ['string', 'int']);
    }
}
