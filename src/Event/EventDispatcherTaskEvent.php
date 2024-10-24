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

namespace CleverAge\ProcessBundle\Event;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcherTaskEvent extends Event
{
    public function __construct(
        protected ProcessState $state,
    ) {
    }

    public function getState(): ProcessState
    {
        return $this->state;
    }

    public function setState(ProcessState $state): void
    {
        $this->state = $state;
    }
}
