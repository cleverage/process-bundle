<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\SerializerTask, use CleverAge\ProcessBundle\Task\Serialization\SerializerTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Serialization\SerializerTask',
    'CleverAge\ProcessBundle\Task\SerializerTask'
);
