<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Event;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Call the Symfony event dispatcher
 * 
 * If defined as passive (which is the default), it automatically set the output from the input
 * 
 * ##### Task reference
 * 
 *  * **Service**: `CleverAge\ProcessBundle\Task\Event\EventDispatcherTask`
 *  * **Input**: `any`
 *  * **Output**:
 *     - `any` when `passive` option is set to true
 *     - `null` in other cases
 * 
 * ##### Options
 * 
 * * `event_name` (`string`, _required_): Format for normalization ("json", "xml", ... an empty string should also work)
 * * `passive` (`bool`, _defaults to_ `true`): Pass input to output
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
     * @internal
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        if ($options['passive']) {
            $state->setOutput($state->getInput());
        }

        $event = new EventDispatcherTaskEvent($state);

        $this->eventDispatcher->dispatch($event, $options['event_name']);
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
                'event_name',
            ]
        );
        $resolver->setDefault('passive', true);
        $resolver->setAllowedTypes('event_name', ['string']);
        $resolver->setAllowedTypes('passive', ['boolean']);
    }
}
