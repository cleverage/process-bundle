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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Unset a given property
 */
class UnsetTransformer implements ConfigurableTransformerInterface
{
    use ConditionTrait;

    /**
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value, array $options = [])
    {
        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Given value must be an array');
        }

        if (!array_key_exists($options['property'], $value)) {
            throw new \UnexpectedValueException("Property {$options['property']} does not exists");
        }

        if ($this->checkCondition($value, $options['condition'])) {
            unset($value[$options['property']]);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('property');
        $resolver->setAllowedTypes('property', 'string');

        $this->configureWrappedConditionOptions('condition', $resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'unset';
    }
}
