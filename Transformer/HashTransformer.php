<?php declare(strict_types=1);
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

/**
 * Use hash() function to generate hash value
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class HashTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('algo');
        $resolver->setAllowedValues('algo', hash_algos());
        $resolver->setAllowedTypes('algo', 'string');

        $resolver->setDefined('raw_output');
        $resolver->setDefault('raw_output', false);
    }

    /**
     * {@inheritDoc}
     * @throws \UnexpectedValueException
     */
    public function transform($value, array $options = [])
    {
        return hash($options['algo'], $value, $options['raw_output']);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'hash';
    }
}
