<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Applies transformers to each element of an array.
 */
class ArrayMapTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    public function __construct(TransformerRegistry $transformerRegistry)
    {
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * Must return the transformed $value.
     */
    public function transform(mixed $value, array $options = []): array
    {
        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new \UnexpectedValueException('Input value must be an array or traversable');
        }

        $results = [];
        foreach ($value as $key => $item) {
            try {
                $item = $this->applyTransformers($options['transformers'], $item);
                if (null === $item && $options['skip_null']) {
                    continue;
                }
                $results[$key] = $item;
            } catch (TransformerException $exception) {
                $exception->setTargetProperty((string) $key);
                throw $exception;
            }
        }

        return $results;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'array_map';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureTransformersOptions($resolver);
        $resolver->setRequired(['transformers']);
        $resolver->setDefaults([
            'skip_null' => false,
        ]);
        $resolver->setAllowedTypes('skip_null', ['boolean']);
    }
}
