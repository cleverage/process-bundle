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

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Iterate over a static pre-defined set of values.
 *
 * Always send the same output regardless of the input, only accepts array for values and iterate over it.
 *
 * Same as {@see ConstantOutputTask} iterate over values.
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\ConstantIterableOutputTask`
 *  * **Iterable task**
 *  * **Input**: _ignored_
 *  * **Output**: `any`, values from the `output` option
 *
 * ##### Options
 *
 *  * **`output`** (`array`, _required_): Array of values to iterate onto
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ConstantIterableOutputTask extends AbstractIterableOutputTask
{
    /**
     * {@inheritDoc}
     *
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'output',
            ]
        );
        $resolver->setAllowedTypes('output', ['array']);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ExceptionInterface
     * @throws \InvalidArgumentException
     */
    protected function initializeIterator(ProcessState $state): \Iterator
    {
        return new \ArrayIterator($this->getOption($state, 'output'));
    }
}
