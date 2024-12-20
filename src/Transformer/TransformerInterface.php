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
 * Transforms a value to an other.
 */
interface TransformerInterface
{
    /**
     * Must return the transformed $value.
     */
    public function transform(mixed $value, array $options = []): mixed;

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string;
}
