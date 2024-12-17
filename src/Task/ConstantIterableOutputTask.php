<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Always send the same output regardless of the input, only accepts array for values and iterate over it.
 */
class ConstantIterableOutputTask extends AbstractIterableOutputTask
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['output']);
        $resolver->setAllowedTypes('output', ['array']);
    }

    protected function initializeIterator(ProcessState $state): \Iterator
    {
        return new \ArrayIterator($this->getOption($state, 'output'));
    }
}
