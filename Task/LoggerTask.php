<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\LoggerTask, use CleverAge\ProcessBundle\Task\Reporting\LoggerTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Reporting\LoggerTask',
    'CleverAge\ProcessBundle\Task\LoggerTask'
);
