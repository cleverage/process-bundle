<?php

namespace CleverAge\ProcessBundle\Context;


class ContextualOptionResolver
{
    /**
     * Basic value inference
     * Replaces "{{ key }}" by context[key]
     *
     * @param string $value
     * @param array  $context
     *
     * @return mixed
     */
    public function contextualizeOption($value, array $context)
    {
        $keys = [];
        if (is_string($value) && preg_match('/^{{ *([a-z0-9_\-]+) *}}$/', $value, $keys) && isset($keys[1])) {
            if (array_key_exists($keys[1], $context)) {
                return $context[$keys[1]];
            }
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
            $contextualizedOptions[$key] = $this->contextualizeOption($value, $context);
        }

        return $contextualizedOptions;
    }
}
