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

namespace CleverAge\ProcessBundle\Task\Reporting;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class LoggerTask.
 *
 * Add custom log in state
 */
class LoggerTask extends AbstractConfigurableTask
{
    public function __construct(
        protected LoggerInterface $logger,
        protected PropertyAccessorInterface $accessor
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $context = [];
        foreach ($options['context'] as $contextInfo) {
            $context[$contextInfo] = $this->accessor->getValue($state, $contextInfo);
        }
        if ($options['reference']) {
            $context['reference'] = $options['reference'];
        }
        $this->logger->log($options['level'], $options['message'], $context);

        $state->setOutput($state->getInput());
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'level' => 'debug',
                'message' => 'Log state input',
                'context' => ['input'],
                'reference' => null,
            ]
        );
        $resolver->setAllowedTypes('level', ['string']);
        $resolver->setAllowedTypes('message', ['string']);
        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setAllowedTypes('reference', ['string', 'null']);
    }
}
