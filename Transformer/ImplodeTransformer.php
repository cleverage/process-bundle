<?php
/**
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
 * Implode multiple array values to a string, based on a split character
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 * @author Corentin Bouix <cbouix@clever-age.com>
 */
class ImplodeTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('separator');
        $resolver->setDefault('separator', '|');
        $resolver->setAllowedTypes('separator', 'string');
    }

    /**
     * {@inheritDoc}
     * @throws \UnexpectedValueException
     */
    public function transform($value, array $options = [])
    {
        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Given value is not an array');
        }

        return implode($options['separator'], $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'implode';
    }
}
