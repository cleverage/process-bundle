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

use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Group inputs using a given property, splitting common values and specific values.
 *
 * For each input this task will get the value under the property defined by the `aggregate_by` option, and try to aggregate similar inputs.
 * The idea is to have one level of common values, and one level of specific values.
 *
 * ##### Task reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Task\RowAggregatorTask`
 * * **Blocking task**
 * * **Input**: `array`
 * * **Output**: `array` of `array`
 *    - each 1st level item has for key a value from the property defined by the `aggregate_by` option
 *    - the 2nd level is an `array` with all the values from the FIRST match that are NOT in `aggregate_columns`
 *          + an additional property defined by the `aggregation_key`
 *    - under the 3rd level, defined by `aggregation_key`, there will be a list of item,
 *          each containing values copied for the columns defined by `aggregate_columns`
 *
 * ##### Options
 *
 * * `aggregate_by` (`string`, _required_): the property that will be used for aggregation
 * * `aggregation_key` (`string`, _required_): the key in each item of the output that will contain the list of copied input item
 * * `aggregate_columns` (`array`, _required_): the list of properties that will be copied for each aggregated item
 *
 * @example "Resources/examples/task/row_aggregator_task.yaml"
 */
class RowAggregatorTask extends AbstractConfigurableTask implements BlockingTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();

        $aggregateBy = $this->getOption($state, 'aggregate_by');
        $aggregateColumns = $this->getOption($state, 'aggregate_columns');
        $aggregationKey = $this->getOption($state, 'aggregation_key');

        if (!array_key_exists($aggregateBy, $input)) {
            throw new InvalidProcessConfigurationException(
                "Array aggregator exception: missing column '{$aggregateBy}'"
            );
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
                throw new InvalidProcessConfigurationException(
                    "Array aggregator exception: missing column {$aggregateColumn}"
                );
            }
            $inputAggregateColumns[$aggregateColumn] = $input[$aggregateColumn];
        }
        $this->result[$inputAggregateBy][$aggregationKey][] = $inputAggregateColumns;
    }

    /**
     * {@inheritDoc}
     * @internal
     */
    public function proceed(ProcessState $state)
    {
        $state->setOutput(array_values($this->result));
    }

    /**
     * {@inheritDoc}
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('aggregate_by');
        $resolver->setRequired('aggregate_columns');
        $resolver->setRequired('aggregation_key');
        $resolver->setAllowedTypes('aggregate_by', 'string');
        $resolver->setAllowedTypes('aggregate_columns', 'array');
        $resolver->setAllowedTypes('aggregation_key', 'string');
    }
}
