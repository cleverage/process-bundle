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

use Symfony\Component\VarDumper\VarDumper;

/**
 * Simple dump in a transformer, passthrough for value
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DebugTransformer implements TransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform($value, array $options = [])
    {
        if (class_exists(VarDumper::class)) {
            VarDumper::dump($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return 'dump';
    }
}
