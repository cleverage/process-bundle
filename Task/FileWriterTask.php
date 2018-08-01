<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\FileWriterTask, use CleverAge\ProcessBundle\Task\File\FileWriterTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\FileWriterTask',
    'CleverAge\ProcessBundle\Task\FileWriterTask'
);
