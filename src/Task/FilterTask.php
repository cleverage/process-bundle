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
use CleverAge\ProcessBundle\Transformer\ConditionTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Skip inputs under given matching conditions
 * - equality is softly checked
 * - unexisting key is the same as null.
 */
class FilterTask extends AbstractConfigurableTask
{
    use ConditionTrait;

    public function initialize(ProcessState $state): void
    {
        parent::initialize($state);
        $this->accessor = new PropertyAccessor();
    }

    public function execute(ProcessState $state): void
    {
        $input = $state->getInput();
        if (!$this->checkCondition($input, $this->getOptions($state))) {
            $state->setErrorOutput($input);
            $state->setSkipped(true);

            return;
        }

        $state->setOutput($input);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureConditionOptions($resolver);
    }
}
