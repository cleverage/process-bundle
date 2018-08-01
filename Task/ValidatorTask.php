<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\ValidatorTask, use CleverAge\ProcessBundle\Task\Validation\ValidatorTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Validation\ValidatorTask',
    'CleverAge\ProcessBundle\Task\ValidatorTask'
);
