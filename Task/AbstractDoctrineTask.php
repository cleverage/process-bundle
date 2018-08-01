<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\AbstractDoctrineTask, use CleverAge\ProcessBundle\Task\Doctrine\AbstractDoctrineTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Doctrine\AbstractDoctrineTask',
    'CleverAge\ProcessBundle\Task\AbstractDoctrineTask'
);
