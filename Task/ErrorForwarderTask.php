<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\ErrorForwarderTask, use CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask',
    'CleverAge\ProcessBundle\Task\ErrorForwarderTask'
);
