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
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Aggregates data from heterogeneous inputs.
 * 
 * It skips output until input is received from every parent task. Then, the internal data is reset (except for `keep_inputs` indexes).
 * 
 * Warning : the `clean_input_on_override` option can be dangerous if set to `false`. Especially in loops (iterable process), there can be cases where the inputs are mixed between the iterations of an input... (ex: one of the parent task has skipped output due to an error). Even on `true`, some case have been determined to be problematic.
 * 
 * The usage of this task is therefore **strongly discouraged**, unless you are using it in a non-iterable process. It may one day evolve in a Blocking Task.
 *
 * ##### Task reference
 * 
 * * **Service**: `CleverAge\ProcessBundle\Task\InputAggregatorTask`
 * * **Input**: `any`
 * * **Output**: `array`, list of index destination => values from previous tasks
 * 
 * ##### Options
 * 
 * * `input_codes` (`array`, _required_): List of task code => index destination
 * * `clean_input_on_override` (`bool`, _defaults to_ `true`): Empty the future output if there any override
 * * `keep_inputs` (`array` or `null`, _defaults to_ `null`): List of index  * destination to keep on flush
 *
 * @deprecated It's way too much error prone - should be refactored as a blocking task
 */
class InputAggregatorTask extends AbstractConfigurableTask
{
    /** @var array */
    protected $inputs = [];

    /**
     * Store inputs and once everything has been received, pass to next task
     * Once an output has been generated this task is reset, and may wait for another loop
     *
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $previousState = $state->getPreviousState();
        if (!$previousState || !$previousState->getTaskConfiguration()) {
            throw new \UnexpectedValueException('This task cannot be used without a previous task');
        }

        $inputCode = $this->getInputCode($state);
        if (array_key_exists($inputCode, $this->inputs)) {
            if ($this->getOption($state, 'clean_input_on_override')) {
                $this->inputs = [];
            } else {
                throw new \UnexpectedValueException(
                    "The output from input '{$inputCode}' has already been defined, please use an aggregator if you have an iterable output"
                );
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

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('input_codes');
        $resolver->setDefaults(
            [
                'clean_input_on_override' => true,
                'keep_inputs' => null,
            ]
        );
        $resolver->setAllowedTypes('input_codes', 'array');
        $resolver->setAllowedTypes('clean_input_on_override', 'boolean');
        $resolver->setAllowedTypes('keep_inputs', ['null', 'array']);
    }

    /**
     * Map the previous task code to an input code
     *
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    protected function getInputCode(ProcessState $state)
    {
        $previousState = $state->getPreviousState();
        if (!$previousState) {
            throw new \RuntimeException('No previous state for current task');
        }
        $previousTaskCode = $previousState->getTaskConfiguration()->getCode();
        $inputCodes = $this->getOption($state, 'input_codes');
        if (!array_key_exists($previousTaskCode, $inputCodes)) {
            throw new \UnexpectedValueException("Task '{$previousTaskCode}' is not mapped in the input_codes option");
        }

        return $inputCodes[$previousTaskCode];
    }

    /**
     * Check if the received inputs match the defined mappings
     *
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function isResolved(ProcessState $state)
    {
        $inputCodes = $this->getOption($state, 'input_codes');
        foreach ($inputCodes as $inputCode) {
            if (!array_key_exists($inputCode, $this->inputs)) {
                return false;
            }
        }

        return true;
    }
}
