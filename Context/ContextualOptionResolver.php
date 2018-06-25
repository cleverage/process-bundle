<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Context;

/**
 * Class ContextualOptionResolver
 *
 * @author  Valentin Clavreul <vclavreul@clever-age.com>
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class ContextualOptionResolver
{
    /**
     * Basic value inference
     * Replaces "{{ key }}" by context[key]
     *
     * @param string|array $value
     * @param array        $context
     *
     * @return mixed
     */
    public function contextualizeOption($value, array $context)
    {
        // Recursively parse options
        if (\is_array($value)) {
            return $this->contextualizeOptions($value, $context);
        }

        if (\is_string($value)) {
            $pattern = sprintf('/{{[ ]*(%s){1}[ ]*}}/', implode('|', array_keys($context)));

            $matches = [];
            $result = preg_match($pattern, $value, $matches);

            // If it's an exact match, directly returns the value (allowing complex values such as an array)
            if ($result && $matches[0] === $value) {
                return $context[$matches[1]];
            }

            // Else use a replace to insert a string value into another
            return preg_replace_callback(
                $pattern,
                function ($matches) use ($context) {
                    return $context[$matches[1]];
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Replace all contextualized values from options
     *
     * @param array $options
     * @param array $context
     *
     * @return array
     */
    public function contextualizeOptions(array $options, array $context): array
    {
        $contextualizedOptions = [];
        foreach ($options as $key => $value) {
            $contextualizedKey = $this->contextualizeOption($key, $context);
            $contextualizedValue = $this->contextualizeOption($value, $context);
            $contextualizedOptions[$contextualizedKey] = $contextualizedValue;
        }

        return $contextualizedOptions;
    }
}
