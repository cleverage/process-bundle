<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Set a property on the input and return it.
 * 
 * See [PropertyAccess Component Reference](https://symfony.com/doc/current/components/property_access.html) for details on property path syntax and behavior.
 * 
 * ##### Task reference
 * 
 *  * **Service**: `CleverAge\ProcessBundle\Task\PropertySetterTask`
 *  * **Input**: `array` or `object` that can be accessed by the property accessor
 *  * **Output**: Same `array` or `object`, with the property changed
 * 
 * ##### Options
 *
 * * `values` (`array`, _required_): List of property path => value to set in the input
 * 
 * 
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class PropertySetterTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger, PropertyAccessorInterface $accessor)
    {
        $this->logger = $logger;
        $this->accessor = $accessor;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $input = $state->getInput();
        /** @noinspection ForeachSourceInspection */
        foreach ($options['values'] as $key => $value) {
            try {
                $this->accessor->setValue($input, $key, $value);
            } catch (\Exception $e) {
                $state->addErrorContextValue('property', $key);
                $state->addErrorContextValue('value', $value);
                $state->setException($e);

                return;
            }
        }

        $state->setOutput($input);
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'values',
            ]
        );
        $resolver->setAllowedTypes('values', ['array']);
    }
}
