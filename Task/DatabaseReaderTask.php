<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DatabaseReaderTask, use CleverAge\ProcessBundle\Task\Database\DatabaseReaderTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Database\DatabaseReaderTask',
    'CleverAge\ProcessBundle\Task\DatabaseReaderTask'
);
