<?php


namespace CleverAge\ProcessBundle\Event;

/**
 * This class aims to provide compatibility between different Symfony versions
 *
 * @deprecated once sf3.4 support is dropped, only keep the "Contracts"
 */
if (class_exists('\Symfony\Component\EventDispatcher\Event')) {
    class GenericEvent extends \Symfony\Component\EventDispatcher\Event
    {
    }
} else {
    class GenericEvent extends \Symfony\Contracts\EventDispatcher\Event
    {
    }
}

