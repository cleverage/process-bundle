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
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Outputs a static pre-defined value
 *
 * Simply outputs the same configured value all the time, ignores any input
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\ConstantOutputTask`
 *  * **Input**: _ignored_
 *  * **Output**: `any`, value from the `output` option
 *
 * ##### Options
 *
 * * `output` (_required_): Value to output
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ConstantOutputTask extends AbstractConfigurableTask
{
    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $state->setOutput($this->getOption($state, 'output'));
    }

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
    }
}
