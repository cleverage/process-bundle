<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DatabaseUpdaterTask, use CleverAge\ProcessBundle\Task\Database\DatabaseUpdaterTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Database\DatabaseUpdaterTask',
    'CleverAge\ProcessBundle\Task\DatabaseUpdaterTask'
);
