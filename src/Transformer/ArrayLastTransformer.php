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

/**
 * Return the last element of an array.
 */
class ArrayLastTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed
    {
        return array_values(\array_slice($value, -1))[0];
    }

    public function getCode(): string
    {
        return 'array_last';
    }
}
