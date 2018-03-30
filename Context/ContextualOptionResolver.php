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
