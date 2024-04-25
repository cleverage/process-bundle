<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Wait for defined inputs before passing an aggregated output.
 * Should have been a BlockingTask, but due to limitations in the current model, it's a hack using skips and finalize.
 *
 * @see        README.md:Known issues
 * @deprecated It's way too much error prone - should be refactored as a blocking task
 */
class InputAggregatorTask extends AbstractConfigurableTask
{
    protected array $inputs = [];

    /**
     * Store inputs and once everything has been received, pass to next task
     * Once an output has been generated this task is reset, and may wait for another loop.
     */
    public function execute(ProcessState $state): void
    {
        $previousState = $state->getPreviousState();
        if (!$previousState || !$previousState->getTaskConfiguration()) {
            throw new \UnexpectedValueException('This task cannot be used without a previous task');
        }

        $inputCode = $this->getInputCode($state);
        if (\array_key_exists($inputCode, $this->inputs)) {
            if ($this->getOption($state, 'clean_input_on_override')) {
                $this->inputs = [];
            } else {
                throw new \UnexpectedValueException("The output from input '{$inputCode}' has already been defined, please use an aggregator if you have an iterable output");
            }
        }

        $this->inputs[$inputCode] = $state->getInput();

        if ($this->isResolved($state)) {
            $state->setOutput($this->inputs);
            $keepInputs = $this->getOption($state, 'keep_inputs');
            // Only clear inputs that are not in the keep_inputs option
            foreach ($this->inputs as $inputCode => $value) {
                if (null !== $keepInputs && \in_array($inputCode, $keepInputs, true)) {
                    continue;
                }
                unset($this->inputs[$inputCode]);
            }
        } else {
            $state->setSkipped(true);
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('input_codes');
        $resolver->setDefaults([
            'clean_input_on_override' => true,
            'keep_inputs' => null,
        ]);
        $resolver->setAllowedTypes('input_codes', 'array');
        $resolver->setAllowedTypes('clean_input_on_override', 'boolean');
        $resolver->setAllowedTypes('keep_inputs', ['null', 'array']);
    }

    /**
     * Map the previous task code to an input code.
     */
    protected function getInputCode(ProcessState $state): string
    {
        $previousState = $state->getPreviousState();
        if (!$previousState) {
            throw new \RuntimeException('No previous state for current task');
        }
        $previousTaskCode = $previousState->getTaskConfiguration()
            ->getCode();
        $inputCodes = $this->getOption($state, 'input_codes');
        if (!\array_key_exists($previousTaskCode, $inputCodes)) {
            throw new \UnexpectedValueException("Task '{$previousTaskCode}' is not mapped in the input_codes option");
        }

        return $inputCodes[$previousTaskCode];
    }

    /**
     * Check if the received inputs match the defined mappings.
     */
    protected function isResolved(ProcessState $state): bool
    {
        $inputCodes = $this->getOption($state, 'input_codes');
        foreach ($inputCodes as $inputCode) {
            if (!\array_key_exists($inputCode, $this->inputs)) {
                return false;
            }
        }

        return true;
    }
}
