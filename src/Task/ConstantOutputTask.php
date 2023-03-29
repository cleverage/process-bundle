<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Always send the same output regardless of the input
 */
class ConstantOutputTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $state->setOutput($this->getOption($state, 'output'));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['output']);
    }
}
