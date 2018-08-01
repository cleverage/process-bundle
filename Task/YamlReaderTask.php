<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\YamlReaderTask, use CleverAge\ProcessBundle\Task\File\YamlReaderTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\YamlReaderTask',
    'CleverAge\ProcessBundle\Task\YamlReaderTask'
);
