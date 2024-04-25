<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Get a property on the input and return it with PropertyAccessor.
 */
class PropertyGetterTask extends AbstractConfigurableTask
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
        $property = $options['property'];

        try {
            $output = $this->accessor->getValue($input, $property);
        } catch (\Exception $e) {
            $state->addErrorContextValue('property', $property);
            $state->setException($e);

            return;
        }

        $state->setOutput($output);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['property']);
        $resolver->setAllowedTypes('property', ['string']);
    }
}
