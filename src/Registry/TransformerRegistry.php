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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;

/**
 * Holds all tagged transformer services.
 */
class TransformerRegistry
{
    /**
     * @var TransformerInterface[]
     */
    protected array $transformers = [];

    public function addTransformer(TransformerInterface $transformer): void
    {
        if (\array_key_exists($transformer->getCode(), $this->transformers)) {
            throw new \UnexpectedValueException("Transformer {$transformer->getCode()} is already defined");
        }
        $this->transformers[$transformer->getCode()] = $transformer;
    }

    /**
     * @return TransformerInterface[]
     */
    public function getTransformers(): array
    {
        return $this->transformers;
    }

    public function getTransformer(string $code): TransformerInterface
    {
        if (!$this->hasTransformer($code)) {
            throw MissingTransformerException::create($code);
        }

        return $this->transformers[$code];
    }

    public function hasTransformer(string $code): bool
    {
        return \array_key_exists($code, $this->transformers);
    }
}
