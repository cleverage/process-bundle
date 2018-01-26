<?php

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Wait for defined inputs before passing an aggregated output.
 * Should have been a BlockingTask, but due to limitations in the current model, it's a hack using skips and finalize.
 *
 * @see README.md:Known issues
 */
class InputAggregatorTask extends AbstractConfigurableTask implements FinalizableTaskInterface
{
    /** @var array */
    protected $inputs = [];

    /**
     * Store inputs and once everything has been received, pass to next task
     * Once an output has been generated this task is reset, and may wait for another loop
     *
     * @param ProcessState $state
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $previousState = $state->getPreviousState();
        if (!$previousState || !$previousState->getTaskConfiguration()) {
            throw new \UnexpectedValueException("This task cannot be used without a previous task");
        }

        $cleanInputOnOverride = $this->getOption($state, 'clean_input_on_override');

        $inputCode = $this->getInputCode($state);
        if (array_key_exists($inputCode, $this->inputs)) {
            if ($cleanInputOnOverride) {
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
            $this->inputs = [];
        } else {
            $state->setSkipped(true);
        }

    }

    /**
     * If there is pending inputs, something went wrong
     *
     * @param ProcessState $state
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function finalize(ProcessState $state)
    {
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('input_codes');
        $resolver->setDefault('clean_input_on_override', true);
        $resolver->setAllowedTypes('input_codes', 'array');
        $resolver->setAllowedTypes('clean_input_on_override', 'boolean');
    }

    /**
     * Map the previous task code to an input code
     *
     * @param ProcessState $state
     *
     * @return string
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
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

    /**
     * Check if the received inputs match the defined mappings
     *
     * @param ProcessState $state
     *
     * @return bool
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
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
