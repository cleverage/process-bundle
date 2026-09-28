<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Event;

use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Call the Symfony event dispatcher
 * If defined as passive (which is the default), it automatically set the output from the input.
 */
class EventDispatcherTask extends AbstractConfigurableTask
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        if ($options['passive']) {
            $state->setOutput($state->getInput());
        }

        $event = new EventDispatcherTaskEvent($state);

        $this->eventDispatcher->dispatch($event, $options['event_name']);

        // @deprecated BC layer since v5, remove me in v6.0: from v4.0 to v5.0, the event was only dispatched under its
        // class name, even when event_name was set
        if (null !== $options['event_name']
            && EventDispatcherTaskEvent::class !== $options['event_name']
            && $this->eventDispatcher->hasListeners(EventDispatcherTaskEvent::class)
        ) {
            @trigger_error(
                \sprintf(
                    'Listening to "%s" for an EventDispatcherTask with the "event_name" option set is deprecated since v5 and will not work anymore in v6.0, listen to "%s" instead.',
                    EventDispatcherTaskEvent::class,
                    $options['event_name'],
                ),
                \E_USER_DEPRECATED
            );
            $this->eventDispatcher->dispatch($event);
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('event_name', null);
        $resolver->setDefault('passive', true);
        $resolver->setAllowedTypes('event_name', ['null', 'string']);
        $resolver->setAllowedTypes('passive', ['boolean']);
    }
}
