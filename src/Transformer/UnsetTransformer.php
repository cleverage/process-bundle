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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Unset a given property.
 */
class UnsetTransformer implements ConfigurableTransformerInterface
{
    use ConditionTrait;

    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    public function transform(mixed $value, array $options = []): array
    {
        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Given value must be an array');
        }

        if (!\array_key_exists($options['property'], $value)) {
            throw new \UnexpectedValueException("Property {$options['property']} does not exists");
        }

        if ($this->checkCondition($value, $options['condition'])) {
            unset($value[$options['property']]);
        }

        return $value;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('property');
        $resolver->setAllowedTypes('property', 'string');

        $this->configureWrappedConditionOptions('condition', $resolver);
    }

    public function getCode(): string
    {
        return 'unset';
    }
}
