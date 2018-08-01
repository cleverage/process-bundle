<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\DoctrineWriterTask, use CleverAge\ProcessBundle\Task\Doctrine\DoctrineWriterTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Doctrine\DoctrineWriterTask',
    'CleverAge\ProcessBundle\Task\DoctrineWriterTask'
);
