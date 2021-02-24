<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
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
    protected const MERGE_FUNC = ['array_merge', 'array_merge_recursive', 'array_replace', 'array_replace_recursive'];

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
        if (!\in_array($mergeFunction, self::MERGE_FUNC, true)) {
            throw new \InvalidArgumentException("Unknown merge function {$mergeFunction}");
        }
        $this->mergedOutput = $mergeFunction($this->mergedOutput, $input);
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        $state->setOutput($this->mergedOutput);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('merge_function', 'array_merge');
        $resolver->setAllowedTypes('merge_function', 'string');
        $resolver->setAllowedValues('merge_function', self::MERGE_FUNC);
    }
}
