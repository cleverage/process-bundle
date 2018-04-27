<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Wait for defined inputs before passing an aggregated output.
 * Should have been a BlockingTask, but due to limitations in the current model, it's a hack using skips and finalize.
 *
 * @see README.md:Known issues
 */
class RowAggregatorTask extends AbstractConfigurableTask implements BlockingTaskInterface
{
    /**
     * @var array
     */
    protected $result = [];

    /**
     * Store inputs and once everything has been received, pass to next task
     * Once an output has been generated this task is reset, and may wait for another loop
     *
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();

        $aggregateBy = $this->getOption($state, 'aggregate_by');
        $aggregateColumns = $this->getOption($state, 'aggregate_columns');
        $aggregationKey = $this->getOption($state, 'aggregation_key');

        if (!array_key_exists($aggregateBy, $input)) {
            $state->setError($state->getInput());
            $message = sprintf('Array aggregator exception: missing column %s', $aggregateBy);
            $this->logger->error($message, $state->getLogContext());
            if ($this->getOption($state, self::ERROR_STRATEGY) === self::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($this->getOption($state, self::ERROR_STRATEGY) === self::STRATEGY_STOP) {
                $state->stop(new InvalidProcessConfigurationException($message));
            }

            return;
        }

        $inputAggregateBy = $input[$aggregateBy];

        if (!array_key_exists($inputAggregateBy, $this->result)) {
            $this->result[$inputAggregateBy] = $input;
            foreach ($aggregateColumns as $aggregateColumn) {
                if (array_key_exists($aggregateColumn, $this->result[$inputAggregateBy])) {
                    unset($this->result[$inputAggregateBy][$aggregateColumn]);
                }
            }
        }

        $inputAggregateColumns = [];
        foreach ($aggregateColumns as $aggregateColumn) {
            if (!array_key_exists($aggregateColumn, $input)) {
                $message = sprintf('Array aggregator exception: missing column %s', $aggregateColumn);
                $state->setError($state->getInput());
                $this->logger->error($message, $state->getLogContext());
                if ($this->getOption($state, self::ERROR_STRATEGY) === self::STRATEGY_SKIP) {
                    $state->setSkipped(true);
                } elseif ($this->getOption($state, self::ERROR_STRATEGY) === self::STRATEGY_STOP) {
                    $state->stop(new InvalidProcessConfigurationException($message));
                }

                return;
            }
            $inputAggregateColumns[$aggregateColumn] = $input[$aggregateColumn];
        }
        $this->result[$inputAggregateBy][$aggregationKey][] = $inputAggregateColumns;
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        $state->setOutput(array_values($this->result));
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('aggregate_by');
        $resolver->setRequired('aggregate_columns');
        $resolver->setRequired('aggregation_key');
        $resolver->setAllowedTypes('aggregate_by', 'string');
        $resolver->setAllowedTypes('aggregate_columns', 'array');
        $resolver->setAllowedTypes('aggregation_key', 'string');
    }
}
