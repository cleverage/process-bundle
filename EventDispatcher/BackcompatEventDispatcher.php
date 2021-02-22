<?php

namespace CleverAge\ProcessBundle\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class BackcompatEventDispatcher implements ContractsEventDispatcherInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch($event, string $eventName = null)
    {
        if($this->dispatcher instanceof ContractsEventDispatcherInterface) {
            $this->dispatcher->dispatch($event, $eventName);
        } else {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }
}
