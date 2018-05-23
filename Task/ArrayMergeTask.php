<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Merge every input array, and return the result
 */
class ArrayMergeTask implements BlockingTaskInterface
{
    /** @var array */
    protected $mergedOutput = [];

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        if (!\is_array($input)) {
            throw new \UnexpectedValueException('Input must be an array');
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
