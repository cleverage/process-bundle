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

namespace CleverAge\ProcessBundle\Task\Event;

use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Call the Symfony event dispatcher
 * If defined as passive (which is the default), it automatically set the output from the input
 */
class EventDispatcherTask extends AbstractConfigurableTask
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        if ($options['passive']) {
            $state->setOutput($state->getInput());
        }

        $event = new EventDispatcherTaskEvent($state);

        $this->eventDispatcher->dispatch($event);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['event_name']);
        $resolver->setDefault('passive', true);
        $resolver->setAllowedTypes('event_name', ['string']);
        $resolver->setAllowedTypes('passive', ['boolean']);
    }
}
