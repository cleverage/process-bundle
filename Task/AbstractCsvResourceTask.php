<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\AbstractCsvResourceTask, use CleverAge\ProcessBundle\Task\File\Csv\AbstractCsvResourceTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\Csv\AbstractCsvResourceTask',
    'CleverAge\ProcessBundle\Task\AbstractCsvResourceTask'
);
