<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Accepts an object or an array as input and sets values from configuration
 */
class PropertySetterTask extends AbstractConfigurableTask
{
    public function __construct(
        protected LoggerInterface $logger,
        protected PropertyAccessorInterface $accessor
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $input = $state->getInput();
        foreach ($options['values'] as $key => $value) {
            try {
                $this->accessor->setValue($input, $key, $value);
            } catch (Exception $e) {
                $state->addErrorContextValue('property', $key);
                $state->addErrorContextValue('value', $value);
                $state->setException($e);

                return;
            }
        }

        $state->setOutput($input);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['values']);
        $resolver->setAllowedTypes('values', ['array']);
    }
}
