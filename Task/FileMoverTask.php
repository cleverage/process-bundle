<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\FileMoverTask, use CleverAge\ProcessBundle\Task\File\FileMoverTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\FileMoverTask',
    'CleverAge\ProcessBundle\Task\FileMoverTask'
);
