<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\FileRemoverTask, use CleverAge\ProcessBundle\Task\File\FileRemoverTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\File\FileRemoverTask',
    'CleverAge\ProcessBundle\Task\FileRemoverTask'
);
