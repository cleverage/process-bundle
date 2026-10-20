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

use CleverAge\ProcessBundle\Exception\RuntimeException;
use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Joins the outputs of several branches: waits for an input from every parent task declared in input_codes, then
 * outputs them indexed by their destination key. Unlike a blocking task, it outputs once per item.
 *
 * The inputs of an item are told apart by the executions of their common ancestors: when a branch skips an item, the
 * inputs already received for this item are dropped when the inputs of the next item arrive, instead of being joined
 * with them.
 *
 * @final since 5.4
 */
class InputAggregatorTask extends AbstractConfigurableTask
{
    /**
     * @var array<string, mixed>
     */
    protected array $inputs = [];

    /**
     * Execution count of the ancestors of each received input (ancestor state id => execution count), by input code.
     *
     * @var array<string, array<int, int>>
     */
    protected array $inputAncestors = [];

    /**
     * Store inputs and once everything has been received, pass to next task
     * Once an output has been generated this task is reset, and may wait for another loop.
     */
    public function execute(ProcessState $state): void
    {
        $previousState = $state->getPreviousState();
        if (!$previousState instanceof ProcessState) {
            throw new UnexpectedValueException('This task cannot be used without a previous task');
        }

        $inputCode = $this->getInputCode($state);
        $ancestors = $this->getAncestorExecutions($previousState);
        $this->removeInputsOfPreviousItems($state, $inputCode, $ancestors);
        if (\array_key_exists($inputCode, $this->inputs)) {
            if ($this->getOption($state, 'clean_input_on_override')) {
                $this->inputs = [];
                $this->inputAncestors = [];
            } else {
                throw new UnexpectedValueException("The output from input '{$inputCode}' has already been defined, please use an aggregator if you have an iterable output");
            }
        }

        $this->inputs[$inputCode] = $state->getInput();
        $this->inputAncestors[$inputCode] = $ancestors;

        if ($this->isResolved($state)) {
            $state->setOutput($this->inputs);
            $keepInputs = $this->getOption($state, 'keep_inputs');
            // Only clear inputs that are not in the keep_inputs option
            foreach (array_keys($this->inputs) as $inputCode) {
                if (null !== $keepInputs && \in_array($inputCode, $keepInputs, true)) {
                    continue;
                }
                unset($this->inputs[$inputCode], $this->inputAncestors[$inputCode]);
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
        if (!$previousState instanceof ProcessState) {
            throw new RuntimeException('No previous state for current task');
        }
        $previousTaskCode = $previousState->getTaskConfiguration()
            ->getCode();
        $inputCodes = $this->getOption($state, 'input_codes');
        if (!\array_key_exists($previousTaskCode, $inputCodes)) {
            throw new UnexpectedValueException("Task '{$previousTaskCode}' is not mapped in the input_codes option");
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

    /**
     * Execution count of the input's ancestors, from its parent task up to the first task of its branch.
     *
     * @return array<int, int> ancestor state id => execution count
     */
    protected function getAncestorExecutions(ProcessState $previousState): array
    {
        $ancestors = [];
        for ($ancestor = $previousState; $ancestor instanceof ProcessState; $ancestor = $ancestor->getPreviousState()) {
            $id = spl_object_id($ancestor);
            if (\array_key_exists($id, $ancestors)) {
                break;
            }
            $ancestors[$id] = $ancestor->getExecutionCount();
        }

        return $ancestors;
    }

    /**
     * Removes the inputs of the other branches received for a previous item: a common ancestor has been executed again
     * since they were received (e.g. a branch skipped this item). The inputs of keep_inputs are kept.
     *
     * @param array<int, int> $ancestors
     */
    protected function removeInputsOfPreviousItems(ProcessState $state, string $inputCode, array $ancestors): void
    {
        $keepInputs = $this->getOption($state, 'keep_inputs') ?? [];
        foreach ($this->inputAncestors as $receivedInputCode => $receivedAncestors) {
            if ($receivedInputCode === $inputCode || \in_array($receivedInputCode, $keepInputs, true)) {
                continue;
            }
            foreach (array_intersect_key($receivedAncestors, $ancestors) as $id => $executionCount) {
                if ($executionCount !== $ancestors[$id]) {
                    unset($this->inputs[$receivedInputCode], $this->inputAncestors[$receivedInputCode]);
                    break;
                }
            }
        }
    }
}
