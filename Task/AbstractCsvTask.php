<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\AbstractCsvTask, use CleverAge\ProcessBundle\Task\File\Csv\AbstractCsvTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\Csv\AbstractCsvTask',
    'CleverAge\ProcessBundle\Task\AbstractCsvTask'
);
