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
     *
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): mixed;

    /**
     * Returns the unique code to identify the transformer.
     *
     * @deprecated since 5.4, the code of a transformer is given by the "code" attribute of its "cleverage.transformer"
     *             tag or by the #[AsTransformer] attribute; this method will be removed from the interface in v6.0.
     *             Until then, it must return the same code.
     */
    public function getCode(): string;
}
