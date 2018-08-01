<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\ProcessExecutorTask, use CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask',
    'CleverAge\ProcessBundle\Task\ProcessExecutorTask'
);
