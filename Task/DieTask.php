<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DieTask, use CleverAge\ProcessBundle\Task\Debug\DieTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Debug\DieTask',
    'CleverAge\ProcessBundle\Task\DieTask'
);
