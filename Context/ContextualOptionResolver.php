<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Context;

/**
 * Class ContextualOptionResolver
 *
 * @package CleverAge\ProcessBundle\Context
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
        if (is_array($value)) {
            return $this->contextualizeOptions($value, $context);
        }

        if (is_string($value)) {
            $pattern = sprintf('/{{[ ]*(%s){1}[ ]*}}/', implode('|', array_keys($context)));

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
            $contextualizedOptions[$key] = $this->contextualizeOption($value, $context);
        }

        return $contextualizedOptions;
    }
}
