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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Array filtering transformer, should match native array_filter behavior
 * @see https://secure.php.net/manual/fr/function.array-filter.php
 */
class ArrayFilterTransformer implements ConfigurableTransformerInterface
{

    use ConditionTrait;

    /**
     * ColumnAggregatorTask constructor.
     *
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
        if (!(\is_array($value) || $value instanceof \Iterable)) {
            throw new \UnexpectedValueException('Given value is not iterable');
        }

        $result = [];

        foreach ($value as $key => $item) {
            if ($this->checkCondition($item, $options['condition'])) {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'array_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->configureWrappedConditionOptions('condition', $resolver);
    }
}
