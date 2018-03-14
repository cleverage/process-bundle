<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        if (!is_array($input)) {
            throw new \UnexpectedValueException("The given input is not an array");
        }

        foreach ($this->getOption($state, 'match') as $key => $value) {
            if (!$this->checkValue($input, $key, $value)) {
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
        $resolver->setAllowedTypes('not_match', 'array');
        $resolver->setAllowedTypes('match', 'array');
    }

    /**
     * Softly check if an input key match a value, or not
     *
     * @param object|array $input
     * @param string       $key
     * @param mixed        $value
     * @param bool         $match
     *
     * @return bool
     */
    protected function checkValue($input, $key, $value, $match = true)
    {
        if ($this->accessor->isReadable($input, $key)) {
            $currentValue = $this->accessor->getValue($input, $key);
        } else {
            $currentValue = null;
        }

        if ($match && $currentValue != $value) {
            return false;
        } elseif (!$match && $currentValue == $value) {
            return false;
        }

        return true;
    }
}
