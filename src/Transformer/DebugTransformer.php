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

use Symfony\Component\VarDumper\VarDumper;

/**
 * Simple dump in a transformer, passthrough for value
 */
class DebugTransformer implements TransformerInterface
{
    public function transform($value, array $options = [])
    {
        if (class_exists(VarDumper::class)) {
            VarDumper::dump($value);
        }

        return $value;
    }

    public function getCode(): string
    {
        return 'dump';
    }
}
