<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Merge every input array, and return the result
 */
class ArrayMergeTask extends AbstractConfigurableTask implements BlockingTaskInterface
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

        $mergeFunction = $this->getOption($state, 'merge_function');
        if ($mergeFunction == 'array_merge') {
            $this->mergedOutput = \array_merge($this->mergedOutput, $input);
        } elseif ($mergeFunction == 'array_merge_recursive') {
            $this->mergedOutput = \array_merge_recursive($this->mergedOutput, $input);
        } elseif ($mergeFunction == 'array_replace') {
            $this->mergedOutput = \array_replace($this->mergedOutput, $input);
        } elseif ($mergeFunction == 'array_replace_recursive') {
            $this->mergedOutput = \array_replace_recursive($this->mergedOutput, $input);
        } else {
            throw new \InvalidArgumentException("Unknown merge function {$mergeFunction}");
        }
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        $state->setOutput($this->mergedOutput);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('merge_function', 'array_merge');
        $resolver->setAllowedTypes('merge_function', 'string');
        $resolver->setAllowedValues('merge_function', ['array_merge', 'array_merge_recursive', 'array_replace', 'array_replace_recursive']);
    }


}
