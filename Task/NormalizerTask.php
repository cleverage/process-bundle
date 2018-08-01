<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\NormalizerTask, use CleverAge\ProcessBundle\Task\Serialization\NormalizerTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Serialization\NormalizerTask',
    'CleverAge\ProcessBundle\Task\NormalizerTask'
);
