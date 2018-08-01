<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Event;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Call the Symfony event dispatcher
 * If defined as passive (which is the default), it automatically set the output from the input
 *
 * @author  Valentin Clavreul <vclavreul@clever-age.com>
 * @author  Vincent Chalnot <vchalnot@clever-age.com>
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class EventDispatcherTask extends AbstractConfigurableTask
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        if ($options['passive']) {
            $state->setOutput($state->getInput());
        }

        $event = new EventDispatcherTaskEvent($state);

        $this->eventDispatcher->dispatch($options['event_name'], $event);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'event_name',
            ]
        );
        $resolver->setDefault('passive', true);
        $resolver->setAllowedTypes('event_name', ['string']);
        $resolver->setAllowedTypes('passive', ['boolean']);
    }
}
