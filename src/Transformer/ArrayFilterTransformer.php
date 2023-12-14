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
 * Array filtering transformer, should match native array_filter behavior.
 *
 * @see https://secure.php.net/manual/fr/function.array-filter.php
 */
class ArrayFilterTransformer implements ConfigurableTransformerInterface
{
    use ConditionTrait;

    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function transform(mixed $value, array $options = []): array
    {
        if (!is_iterable($value)) {
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

    public function getCode(): string
    {
        return 'array_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureWrappedConditionOptions('condition', $resolver);
    }
}
