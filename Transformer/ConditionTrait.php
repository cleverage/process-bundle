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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Configurable set of conditions to use in tasks or transformers options
 */
trait ConditionTrait
{
    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * Test the input with the given set of conditions
     * True by default
     *
     * @param mixed $input
     * @param array $conditions
     *
     * @return bool
     */
    protected function checkCondition($input, $conditions)
    {
        foreach ($conditions['match'] as $key => $value) {
            if (!$this->checkValue($input, $key, $value)) {
                return false;
            }
        }

        foreach ($conditions['empty'] as $key => $value) {
            if (!$this->checkEmpty($input, $key)) {
                return false;
            }
        }

        foreach ($conditions['match_regexp'] as $key => $value) {
            if (!$this->checkValue($input, $key, $value, true, true)) {
                return false;
            }
        }

        foreach ($conditions['not_match'] as $key => $value) {
            if (!$this->checkValue($input, $key, $value, false)) {
                return false;
            }
        }

        foreach ($conditions['not_empty'] as $key => $value) {
            if ($this->checkEmpty($input, $key)) {
                return false;
            }
        }

        foreach ($conditions['not_match_regexp'] as $key => $value) {
            if (!$this->checkValue($input, $key, $value, false, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Configure available condition rules in a wrapper option
     *
     * @param string          $wrapperKey
     * @param OptionsResolver $resolver
     */
    protected function configureWrappedConditionOptions(string $wrapperKey, OptionsResolver $resolver)
    {
        $resolver->setDefault($wrapperKey, []);
        $resolver->setAllowedTypes($wrapperKey, ['array']);
        $resolver->setNormalizer(
            $wrapperKey,
            function (OptionsResolver $options, $value) {
                $conditionResolver = new OptionsResolver();
                $this->configureConditionOptions($conditionResolver);

                return $conditionResolver->resolve($value);
            }
        );
    }

    /**
     * Configure available condition rules
     *
     * @param OptionsResolver $resolver
     */
    protected function configureConditionOptions(OptionsResolver $resolver)
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
     *
     * @param object|array $input
     * @param string       $key
     * @param mixed        $value
     * @param bool         $shouldMatch
     * @param bool         $regexpMode
     *
     * @throws UnexpectedTypeException
     * @throws AccessException
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    protected function checkValue($input, $key, $value, $shouldMatch = true, $regexpMode = false)
    {
        $currentValue = $this->getValue($input, $key);

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($shouldMatch && !$regexpMode && $currentValue != $value) {
            return false;
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        if (!$shouldMatch && !$regexpMode && $currentValue == $value) {
            return false;
        }

        if ($regexpMode) {
            $pregMatch = preg_match($value, $currentValue);

            if ($shouldMatch && (false === $pregMatch || 0 === $pregMatch)) {
                return false;
            }

            if (!$shouldMatch && (false === $pregMatch || $pregMatch > 0)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the input property is empty or not
     *
     * @param array|object $input
     * @param string       $key
     *
     * @return bool
     */
    protected function checkEmpty($input, $key)
    {
        $currentValue = $this->getValue($input, $key);

        return empty($currentValue);
    }

    /**
     * Soft value getter (return the value or null)
     *
     * @param array|object $input
     * @param string       $key
     *
     * @return mixed|null
     */
    protected function getValue($input, $key)
    {
        if ('' === $key) {
            $currentValue = $input;
        } elseif ($this->accessor->isReadable($input, $key)) {
            $currentValue = $this->accessor->getValue($input, $key);
        } else {
            $currentValue = null;
        }

        return $currentValue;
    }
}
