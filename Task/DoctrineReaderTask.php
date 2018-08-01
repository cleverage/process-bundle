<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DoctrineReaderTask, use CleverAge\ProcessBundle\Task\Doctrine\DoctrineReaderTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Doctrine\DoctrineReaderTask',
    'CleverAge\ProcessBundle\Task\DoctrineReaderTask'
);
