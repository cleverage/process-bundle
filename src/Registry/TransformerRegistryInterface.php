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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;

/**
 * Gives the transformers by code (implemented by TransformerRegistry, autowired by this interface).
 */
interface TransformerRegistryInterface
{
    /**
     * Instantiates every transformer.
     *
     * @return array<string, TransformerInterface> indexed by code
     */
    public function getTransformers(): array;

    /**
     * @throws MissingTransformerException when the transformer does not exist
     */
    public function getTransformer(string $code): TransformerInterface;

    public function hasTransformer(string $code): bool;
}
