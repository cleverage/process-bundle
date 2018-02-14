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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Skip inputs under given matching conditions
 * - equality is softly checked
 * - unexisting key is the same as null
 */
class FilterTask extends AbstractConfigurableTask
{

    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * {@inheritDoc}
     */
    public function initialize(ProcessState $state)
    {
        parent::initialize($state);
        $this->accessor = new PropertyAccessor();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        foreach ($this->getOption($state, 'match') as $key => $value) {
            if (!$this->checkValue($input, $key, $value)) {
                $state->setSkipped(true);

                return;
            }
        }

        foreach ($this->getOption($state, 'match_regexp') as $key => $value) {
            if (!$this->checkValue($input, $key, $value, true, true)) {
                $state->setSkipped(true);

                return;
            }
        }

        foreach ($this->getOption($state, 'not_match') as $key => $value) {
            if (!$this->checkValue($input, $key, $value, false)) {
                $state->setSkipped(true);

                return;
            }
        }

        foreach ($this->getOption($state, 'not_match_regexp') as $key => $value) {
            if (!$this->checkValue($input, $key, $value, false, true)) {
                $state->setSkipped(true);

                return;
            }
        }

        $state->setOutput($input);
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('not_match', []);
        $resolver->setDefault('match', []);
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
     * @return bool
     */
    protected function checkValue($input, $key, $value, $shouldMatch = true, $regexpMode = false)
    {
        if ($this->accessor->isReadable($input, $key)) {
            $currentValue = $this->accessor->getValue($input, $key);
        } else {
            $currentValue = null;
        }

        if ($shouldMatch && !$regexpMode && $currentValue != $value) {
            return false;
        }

        if (!$shouldMatch && !$regexpMode && $currentValue == $value) {
            return false;
        }

        $pregMatch = preg_match($value, $currentValue);
        if ($shouldMatch && $regexpMode && ($pregMatch === false || $pregMatch === 0)) {
            return false;
        }

        if (!$shouldMatch && $regexpMode && ($pregMatch === false || $pregMatch > 0)) {
            return false;
        }

        return true;
    }
}
