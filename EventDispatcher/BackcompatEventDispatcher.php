<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * A dispatcher that is flexible enough to handle every sf version
 * We use the PSR interface that is more simple, since PHP allow this kind of override
 *
 * @deprecated once sf3.4 is fully dropped, only "ContractsEventDispatcherInterface" should be used
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class BackcompatEventDispatcher implements PsrEventDispatcherInterface
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
            // This should match most recent Sf version
            $this->dispatcher->dispatch($event, $eventName);
        } else {
            // Back-compatibility with Sf3 style dispatcher
            $this->dispatcher->dispatch($eventName, $event);
        }
    }
}
