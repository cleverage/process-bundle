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

use CleverAge\ProcessBundle\Exception\TransformerException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TypeSetterTransformer
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class TypeSetterTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('type');
        $resolver->setAllowedValues(
            'type',
            [
                'boolean',
                'bool',
                'integer',
                'int',
                'float',
                'double',
                'string',
                'array',
                'object',
                'null',
            ]
        );
        $resolver->setAllowedTypes('type', 'string');
    }

    /**
     * {@inheritDoc}
     * @throws \UnexpectedValueException
     */
    public function transform($value, array $options = [])
    {
        $return = settype($value, $options['type']);

        if (true === $return) {
            return $value;
        }

        throw new TransformerException("Failed to change value type in {$options['type']}");
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'type_setter';
    }
}
