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

use CleverAge\ProcessBundle\Exception\TransformerException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeSetterTransformer implements ConfigurableTransformerInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('type');
        $resolver->setAllowedValues(
            'type',
            ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string', 'array', 'object', 'null']
        );
        $resolver->setAllowedTypes('type', 'string');
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        $return = settype($value, $options['type']);

        if (true === $return) {
            return $value;
        }

        throw new TransformerException("Failed to change value type in {$options['type']}");
    }

    public function getCode(): string
    {
        return 'type_setter';
    }
}
