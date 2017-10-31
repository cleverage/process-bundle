<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\EventListener;


use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;

/**
 * Class DataQueueEventListener
 * This is a basic queue, mainly aiming to catch data coming out of a process
 * Used mostly for testing purpose
 *
 * @package CleverAge\ProcessBundle\EventListener
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