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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Configurable set of conditions to use in tasks or transformers options
 */
trait ConditionTrait
{
    protected ?PropertyAccessorInterface $accessor = null;

    /**
     * Test the input with the given set of conditions
     * True by default
     */
    protected function checkCondition(mixed $input, array $conditions): bool
    {
        foreach ($conditions['match'] as $key => $value) {
            if (! $this->checkValue($input, $key, $value)) {
                return false;
            }
        }

        foreach ($conditions['empty'] as $key => $value) {
            if (! $this->checkEmpty($input, $key)) {
                return false;
            }
        }

        foreach ($conditions['match_regexp'] as $key => $value) {
            if (! $this->checkValue($input, $key, $value, true, true)) {
                return false;
            }
        }

        foreach ($conditions['not_match'] as $key => $value) {
            if (! $this->checkValue($input, $key, $value, false)) {
                return false;
            }
        }

        foreach ($conditions['not_empty'] as $key => $value) {
            if ($this->checkEmpty($input, $key)) {
                return false;
            }
        }

        foreach ($conditions['not_match_regexp'] as $key => $value) {
            if (! $this->checkValue($input, $key, $value, false, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Configure available condition rules in a wrapper option
     */
    protected function configureWrappedConditionOptions(string $wrapperKey, OptionsResolver $resolver): void
    {
        $resolver->setDefault($wrapperKey, []);
        $resolver->setAllowedTypes($wrapperKey, ['array']);
        $resolver->setNormalizer(
            $wrapperKey,
            function (OptionsResolver $options, $value): array {
                $conditionResolver = new OptionsResolver();
                $this->configureConditionOptions($conditionResolver);

                return $conditionResolver->resolve($value);
            }
        );
    }

    /**
     * Configure available condition rules
     */
    protected function configureConditionOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('not_match', []);
        $resolver->setDefault('match', []);
        $resolver->setDefault('not_empty', []);
        $resolver->setDefault('empty', []);
        $resolver->setDefault('not_match_regexp', []);
        $resolver->setDefault('match_regexp', []);
        $resolver->setAllowedTypes('not_match', 'array');
        $resolver->setAllowedTypes('match', 'array');
        $resolver->setAllowedTypes('not_match_regexp', 'array');
        $resolver->setAllowedTypes('match_regexp', 'array');
    }

    /**
     * Softly check if an input key match a value, or not
     */
    protected function checkValue(object|array $input, string $key, mixed $value, bool $shouldMatch = true, bool $regexpMode = false): bool
    {
        $currentValue = $this->getValue($input, $key);

        if ($shouldMatch && ! $regexpMode && $currentValue !== $value) {
            return false;
        }

        if (! $shouldMatch && ! $regexpMode && $currentValue === $value) {
            return false;
        }

        if ($regexpMode) {
            $pregMatch = preg_match($value, (string) $currentValue);

            if ($shouldMatch && ($pregMatch === false || $pregMatch === 0)) {
                return false;
            }

            if (! $shouldMatch && ($pregMatch === false || $pregMatch > 0)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the input property is empty or not
     */
    protected function checkEmpty(object|array $input, string $key): bool
    {
        $currentValue = $this->getValue($input, $key);

        return empty($currentValue);
    }

    /**
     * Soft value getter (return the value or null)
     */
    protected function getValue(object|array $input, string $key): mixed
    {
        if ($key === '') {
            $currentValue = $input;
        } elseif ($this->accessor->isReadable($input, $key)) {
            $currentValue = $this->accessor->getValue($input, $key);
        } else {
            $currentValue = null;
        }

        return $currentValue;
    }
}
