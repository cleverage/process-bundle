<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DoctrineDetacherTask, use CleverAge\ProcessBundle\Task\Doctrine\DoctrineDetacherTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Doctrine\DoctrineDetacherTask',
    'CleverAge\ProcessBundle\Task\DoctrineDetacherTask'
);
