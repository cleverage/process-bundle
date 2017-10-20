<?php

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Wait for defined inputs before passing an aggregated output.
 * Should have been a BlockingTask, but due to limitations in the current model, it's a hack using skips and finalize.
 */
class InputAggregatorTask extends AbstractConfigurableTask implements FinalizableTaskInterface
{
    /** @var array */
    protected $inputs = [];

    /** @var bool */
    protected $isResolved;

    public function finalize(ProcessState $state)
    {
        if (!$this->isResolved($state)) {
            throw new \UnexpectedValueException("This task has not been resolved");
        }
    }


    public function execute(ProcessState $state)
    {
        $previousState = $state->getPreviousState();
        if (!$previousState || !$previousState->getTaskConfiguration()) {
            throw new \UnexpectedValueException("This task cannot be used without a previous task");
        }

        $inputCode = $this->getInputCode($state);
        if (array_key_exists($inputCode, $this->inputs)) {
            throw new \UnexpectedValueException("The output from input '{$inputCode}' has already been defined, please use an aggregator if you have an iterable output");
        }

        $this->inputs[$inputCode] = $state->getInput();

        if ($this->isResolved($state)) {
            $state->setOutput($this->inputs);
        } else {
            $state->setSkipped(true);
        }

    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('input_codes');
        $resolver->setAllowedTypes('input_codes', 'array');
    }

    protected function getInputCode(ProcessState $state)
    {
        $previousState = $state->getPreviousState();
        $previousTaskCode = $previousState->getTaskConfiguration()->getCode();
        $inputCodes = $this->getOption($state, 'input_codes');
        if (!array_key_exists($previousTaskCode, $inputCodes)) {
            throw new \UnexpectedValueException("Task '{$previousTaskCode}' is not mapped in the input_codes option");
        }

        return $inputCodes[$previousTaskCode];
    }

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