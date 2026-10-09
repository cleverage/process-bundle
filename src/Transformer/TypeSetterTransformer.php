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

    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        settype($value, $options['type']);

        return $value;
    }

    public function getCode(): string
    {
        return 'type_setter';
    }
}
