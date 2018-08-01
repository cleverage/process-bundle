<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\CsvWriterTask, use CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask',
    'CleverAge\ProcessBundle\Task\CsvWriterTask'
);
