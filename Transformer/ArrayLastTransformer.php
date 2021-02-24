<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

/**
 * Return the last element of an array
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ArrayLastTransformer implements TransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $options = [])
    {
        return array_values(array_slice($value, -1))[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'array_last';
    }
}
