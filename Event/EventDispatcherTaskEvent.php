<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Event;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EventDispatcherTaskEvent
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class EventDispatcherTaskEvent extends Event
{
    /**
     * @var ProcessState
     */
    protected $state;

    /**
     * @param ProcessState $state
     */
    public function __construct(ProcessState $state)
    {
        $this->state = $state;
    }

    /**
     * @return ProcessState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param ProcessState $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }
}
