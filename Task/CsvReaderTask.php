<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\CsvReaderTask, use CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask',
    'CleverAge\ProcessBundle\Task\CsvReaderTask'
);
