<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DebugTask, use CleverAge\ProcessBundle\Task\Debug\DebugTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Debug\DebugTask',
    'CleverAge\ProcessBundle\Task\DebugTask'
);
