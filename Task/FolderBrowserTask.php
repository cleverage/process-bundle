<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\FolderBrowserTask, use CleverAge\ProcessBundle\Task\File\FolderBrowserTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\FolderBrowserTask',
    'CleverAge\ProcessBundle\Task\FolderBrowserTask'
);
