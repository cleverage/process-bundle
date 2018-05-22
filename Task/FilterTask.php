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
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException
     * @throws \InvalidArgumentException
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
     * @throws \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     *
     * @return bool
     */
    protected function checkValue($input, $key, $value, $shouldMatch = true, $regexpMode = false)
    {
        if ('' === $key) {
            $currentValue = $input;
        } elseif ($this->accessor->isReadable($input, $key)) {
            $currentValue = $this->accessor->getValue($input, $key);
        } else {
            $currentValue = null;
        }

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
}
