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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Takes an array containing an object and a value updates an object's property with this value, then return the object
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ObjectUpdaterTask extends AbstractConfigurableTask
{
    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state): void
    {
        $input = $state->getInput();
        if (!array_key_exists('object', $input)) {
            throw new \UnexpectedValueException("Missing 'object' key in input array");
        }
        if (!array_key_exists('value', $input)) {
            throw new \UnexpectedValueException("Missing 'value' key in input array");
        }
        $this->accessor->setValue($input['object'], $this->getOption($state, 'property_path'), $input['value']);
        $state->setOutput($input['object']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'property_path',
            ]
        );
    }
}
