<?php

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Task\Database\DatabaseReaderTask;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\Database\DatabaseReaderTask, use CleverAge\ProcessBundle\Task\Database\DatabaseReaderTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Database\DatabaseReaderTask',
    DatabaseReaderTask::class
);
