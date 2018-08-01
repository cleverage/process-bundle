<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\EventDispatcherTask, use CleverAge\ProcessBundle\Task\Event\EventDispatcherTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Event\EventDispatcherTask',
    'CleverAge\ProcessBundle\Task\EventDispatcherTask'
);
