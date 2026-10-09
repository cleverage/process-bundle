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

use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Group input rows sharing the same value for the "aggregate_by" column. Each group is made of the first received row
 * (without the "aggregate_columns"), plus a sub-array under "aggregation_key" listing the "aggregate_columns" values of
 * every row of the group. The groups are output once all previous tasks are resolved.
 */
class RowAggregatorTask extends AbstractConfigurableTask implements BlockingTaskInterface
{
    /**
     * @var array<mixed>
     */
    protected array $result = [];

    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Store inputs and once everything has been received, pass to next task
     * Once an output has been generated this task is reset, and may wait for another loop.
     */
    public function execute(ProcessState $state): void
    {
        $input = $state->getInput();

        $aggregateBy = $this->getOption($state, 'aggregate_by');
        $aggregateColumns = $this->getOption($state, 'aggregate_columns');
        $aggregationKey = $this->getOption($state, 'aggregation_key');

        if (!\array_key_exists($aggregateBy, $input)) {
            throw new InvalidProcessConfigurationException("Array aggregator exception: missing column '{$aggregateBy}'");
        }

        $inputAggregateBy = $input[$aggregateBy];

        if (!\array_key_exists($inputAggregateBy, $this->result)) {
            $this->result[$inputAggregateBy] = $input;
            foreach ($aggregateColumns as $aggregateColumn) {
                if (\array_key_exists($aggregateColumn, $this->result[$inputAggregateBy])) {
                    unset($this->result[$inputAggregateBy][$aggregateColumn]);
                }
            }
        }

        $inputAggregateColumns = [];
        foreach ($aggregateColumns as $aggregateColumn) {
            if (!\array_key_exists($aggregateColumn, $input)) {
                throw new InvalidProcessConfigurationException("Array aggregator exception: missing column {$aggregateColumn}");
            }
            $inputAggregateColumns[$aggregateColumn] = $input[$aggregateColumn];
        }
        $this->result[$inputAggregateBy][$aggregationKey][] = $inputAggregateColumns;
    }

    public function proceed(ProcessState $state): void
    {
        $state->setOutput(array_values($this->result));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('aggregate_by');
        $resolver->setRequired('aggregate_columns');
        $resolver->setRequired('aggregation_key');
        $resolver->setAllowedTypes('aggregate_by', 'string');
        $resolver->setAllowedTypes('aggregate_columns', 'array');
        $resolver->setAllowedTypes('aggregation_key', 'string');
    }
}
