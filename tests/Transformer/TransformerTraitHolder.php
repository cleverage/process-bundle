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

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Exposes the TransformerTrait methods to TransformerTraitTest.
 */
class TransformerTraitHolder
{
    use TransformerTrait;

    public function __construct(?TransformerRegistry $transformerRegistry = null)
    {
        $this->transformerRegistry = $transformerRegistry;
    }

    public function cleanCode(string $code): string
    {
        return $this->getCleanedTransfomerCode($code);
    }

    /**
     * @param array<string, mixed> $transformers
     *
     * @return array<string, \Closure>
     */
    public function resolve(array $transformers): array
    {
        $resolver = new OptionsResolver();
        $this->configureTransformersOptions($resolver);

        return $resolver->resolve(['transformers' => $transformers])['transformers'];
    }

    /**
     * @param array<string, \Closure> $transformers
     */
    public function apply(array $transformers, mixed $value): mixed
    {
        return $this->applyTransformers($transformers, $value);
    }
}
