<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\EventListener;

use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;

/**
 * Class DataQueueEventListener
 * This is a basic queue, mainly aiming to catch data coming out of a process
 * Used mostly for testing purpose
 *
 * @author  Valentin Clavreul <vclavreul@¢levere-age.com>
 */
class DataQueueEventListener
{
    /** @var \SplQueue[] */
    protected $queues = [];

    /**
     * @param EventDispatcherTaskEvent $event
     */
    public function pushData(EventDispatcherTaskEvent $event)
    {
        $queue = $this->getQueue($event->getState()->getProcessConfiguration()->getCode());
        $queue->push(clone $event->getState());
    }

    /**
     * @param string $processName
     *
     * @return \SplQueue
     */
    public function getQueue($processName): \SplQueue
    {
        if (!array_key_exists($processName, $this->queues)) {
            $this->queues[$processName] = new \SplQueue();
        }

        return $this->queues[$processName];
    }
}
