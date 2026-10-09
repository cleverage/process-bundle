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

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Closure;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait TransformerTrait
{
    protected ?TransformerRegistry $transformerRegistry = null;

    /**
     * Transform the list of transformer codes + options into a list of Closure (better performances).
     *
     * The list is either a map of "transformer code => options", or a list whose items are a transformer code (without
     * options) or a single "transformer code => options" map. In a list, the closures are keyed by the transformer code
     * followed by "#" and the item position, to keep the keys unique.
     *
     * @param Options<array<string, mixed>> $options
     * @param array<int|string, mixed>      $transformers
     *
     * @return array<string, \Closure>
     */
    public function normalizeTransformers(Options $options, array $transformers): array
    {
        $transformerClosures = [];
        $isList = array_is_list($transformers);

        foreach ($transformers as $key => $transformerDefinition) {
            if ($isList && \is_int($key)) {
                [$origTransformerCode, $transformerOptions] = $this->parseListedTransformer($transformerDefinition, $key);
                $closureKey = "{$origTransformerCode}#{$key}";
            } else {
                $origTransformerCode = (string) $key;
                $transformerOptions = $transformerDefinition;
                $closureKey = $origTransformerCode;
            }

            $transformerOptionsResolver = new OptionsResolver();
            $transformerCode = $this->getCleanedTransfomerCode($origTransformerCode);
            $transformer = $this->getTransformerRegistry()->getTransformer($transformerCode);
            $transformerOptions = $this->checkTransformerOptions($transformerOptions, $origTransformerCode);
            if ($transformer instanceof ConfigurableTransformerInterface) {
                $transformer->configureOptions($transformerOptionsResolver);
                $transformerOptions = $transformerOptionsResolver->resolve($transformerOptions);
            } elseif (!empty($transformerOptions)) {
                throw new \InvalidArgumentException("Transformer {$origTransformerCode} should not have options");
            }

            $closure = static fn ($value) => $transformer->transform($value, $transformerOptions);
            $transformerClosures[$closureKey] = $closure;
        }

        return $transformerClosures;
    }

    /**
     * @param array<string, \Closure> $transformers
     */
    protected function applyTransformers(array $transformers, mixed $value): mixed
    {
        // Quick return for better perfs
        if ([] === $transformers) {
            return $value;
        }

        foreach ($transformers as $transformerCode => $transformerClosure) {
            try {
                $value = $transformerClosure($value);
            } catch (\Throwable $exception) {
                throw new TransformerException($transformerCode, 0, $exception);
            }
        }

        return $value;
    }

    /**
     * This allows to use transformer codes suffixes to avoid limitations to the "transformers" option using codes as
     * keys This way you can chain multiple times the same transformer. Without this, it would silently call only the
     * 1st one.
     *
     * Any non-empty suffix starting with "#" is accepted (digits, or a name describing the step): the part before the
     * first "#" is used as the transformer code if it is registered.
     *
     * @example
     *     transformers:
     *       callback#1:
     *         callback: array_filter
     *       callback#reverse:
     *         callback: array_reverse
     */
    protected function getCleanedTransfomerCode(string $transformerCode): string
    {
        $match = preg_match('/^([^#]+)#.+$/', $transformerCode, $parts);

        if (1 === $match && $this->getTransformerRegistry()->hasTransformer($parts[1])) {
            return $parts[1];
        }

        return $transformerCode;
    }

    protected function configureTransformersOptions(
        OptionsResolver $resolver,
        string $optionName = 'transformers',
    ): void {
        $resolver->setDefault($optionName, []);
        $resolver->setAllowedTypes($optionName, ['array']);
        $resolver->setNormalizer($optionName, $this->normalizeTransformers(...));
    }

    /**
     * Check the options to always return an array, or fail on unexpected values.
     *
     * @return array<string, mixed>
     */
    private function checkTransformerOptions(mixed $transformerOptions, string $transformerCode): array
    {
        if (\is_array($transformerOptions)) {
            return $transformerOptions;
        }
        if (null === $transformerOptions) {
            return [];
        }

        $type = get_debug_type($transformerOptions);

        throw new \InvalidArgumentException("Options for transformer {$transformerCode} are invalid : found {$type}, expected array or null");
    }

    /**
     * Read the code and the options of an item of a transformer list: either a transformer code (without options), or
     * a single "transformer code => options" map.
     *
     * @return array{string, mixed}
     */
    private function parseListedTransformer(mixed $transformerDefinition, int $position): array
    {
        if (\is_string($transformerDefinition) && '' !== $transformerDefinition) {
            return [$transformerDefinition, null];
        }
        if (\is_array($transformerDefinition) && 1 === \count($transformerDefinition)) {
            $transformerCode = array_key_first($transformerDefinition);
            if (\is_string($transformerCode)) {
                return [$transformerCode, $transformerDefinition[$transformerCode]];
            }
        }

        $type = get_debug_type($transformerDefinition);

        throw new \InvalidArgumentException("Transformer at position {$position} is invalid : found {$type}, expected a transformer code or a single \"code: options\" map");
    }

    private function getTransformerRegistry(): TransformerRegistry
    {
        if (!$this->transformerRegistry instanceof TransformerRegistry) {
            throw new \LogicException('No transformer registry defined');
        }

        return $this->transformerRegistry;
    }
}
