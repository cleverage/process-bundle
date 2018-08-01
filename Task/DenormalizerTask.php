<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DenormalizerTask, use CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask',
    'CleverAge\ProcessBundle\Task\DenormalizerTask'
);
