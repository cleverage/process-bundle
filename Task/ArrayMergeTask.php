<?php

namespace CleverAge\ProcessBundle\Task;


use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Merge every input array, and return the result
 */
class ArrayMergeTask implements BlockingTaskInterface
{

    protected $mergedOutput = [];

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        if (!is_array($input)) {
            throw new \UnexpectedValueException("Input must be an array");
        }

        $this->mergedOutput = array_merge($this->mergedOutput, $input);
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        $state->setOutput($this->mergedOutput);
    }

}
