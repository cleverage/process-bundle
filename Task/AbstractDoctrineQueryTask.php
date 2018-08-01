<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\AbstractDoctrineQueryTask, use CleverAge\ProcessBundle\Task\Doctrine\AbstractDoctrineQueryTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Doctrine\AbstractDoctrineQueryTask',
    'CleverAge\ProcessBundle\Task\AbstractDoctrineQueryTask'
);
