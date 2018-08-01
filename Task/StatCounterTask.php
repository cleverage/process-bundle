<?php

namespace CleverAge\ProcessBundle\Task;

@trigger_error(
    'Deprecated class CleverAge\ProcessBundle\Task\StatCounterTask, use CleverAge\ProcessBundle\Task\Reporting\StatCounterTask instead',
    E_USER_DEPRECATED
);
class_alias(
    'CleverAge\ProcessBundle\Task\Reporting\StatCounterTask',
    'CleverAge\ProcessBundle\Task\StatCounterTask'
);
