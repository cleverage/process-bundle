<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\ProcessLauncherTask, use CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask',
    'CleverAge\ProcessBundle\Task\ProcessLauncherTask'
);
