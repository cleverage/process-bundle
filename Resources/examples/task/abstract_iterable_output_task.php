<?php

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;

class MyIterableTask extends AbstractIterableOutputTask {

    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $data = $state->getInput();

        // Do something with data that will produce an array
        // For example, data might be a code and you can fetch a collection in database
        $array = $data;

        // `\ArrayIterator` is the most simple iterator, but it loads everything in memory
        return new \ArrayIterator($array);
    }
}
