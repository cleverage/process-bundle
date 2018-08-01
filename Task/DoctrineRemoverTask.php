<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DoctrineRemoverTask, use CleverAge\ProcessBundle\Task\Doctrine\DoctrineRemoverTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Doctrine\DoctrineRemoverTask',
    'CleverAge\ProcessBundle\Task\DoctrineRemoverTask'
);
