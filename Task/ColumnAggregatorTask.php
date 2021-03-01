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
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Transformer\ConditionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Takes a list of item, and group them depending on a defined condition for a set of columns.
 *
 * This task allows to "flip" a matrix.
 * It will create one group per given column, and for each row, add the input to the group it matches.
 *
 * The main use case was to invert a table like
 * ```
 * |code |val1|val2|
 * |item1|  X |  X |
 * |item2|    |  X |
 * |item3|  X |    |
 * ```
 *
 * And get a list like
 * * `val1 => [item1, item3]`
 * * `val2 => [item1, item2]`
 *
 * See {@see \CleverAge\ProcessBundle\Tests\Task\ColumnAggregatorTaskTest} for input and output values of the example
 *
 * ##### Task reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Task\ColumnAggregatorTask`
 * * **Blocking task**
 * * **Input**: `array`
 * * **Output**: `array` of `array`, each one containing
 *    - the key defined by the `reference_key` option, where the value is a column name
 *    - the key defined by the `aggregation_key` option, where the value is the list of matching inputs
 *
 * ##### Options
 *
 * * `columns` (`array`, _required_): the list of columns that will be used to group the values
 * * `reference_key` (`string`,  _defaults to_ `column`): the key in the output that will contain the column name
 * * `aggregation_key` (`string`, _defaults to_ `values`): the key in the output that will contain the list of matching values
 * * `condition` (_defaults to_ `[]`): a condition to aggregate value, see {@see \CleverAge\ProcessBundle\Transformer\ConditionTrait} for details.
 * The item that will be tested is an array containing
 *   - `input_column_value` : the value of the current row for one of the columns
 *   - `input` : the full current row
 * * `ignore_missing` (`boolean`, _defaults to_ `false`): if false, triggers an error if a column is not in the input
 *
 * @example "Resources/examples/task/column_aggregator_task.yaml" Simple use case
 * @example "Resources/tests/task/column_aggregator_task.yml" Full test case
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class ColumnAggregatorTask extends AbstractConfigurableTask implements BlockingTaskInterface
{
    use ConditionTrait;

    /**
     * @var array
     */
    protected $result = [];

    /** @var LoggerInterface */
    protected $logger;


    /**
     * ColumnAggregatorTask constructor.
     *
     * @param PropertyAccessorInterface $accessor
     * @param LoggerInterface           $logger
     */
    public function __construct(PropertyAccessorInterface $accessor, LoggerInterface $logger)
    {
        $this->accessor = $accessor;
        $this->logger = $logger;
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        $columns = $this->getOption($state, 'columns');
        $conditions = $this->getOption($state, 'condition');

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!isset($input[$column])) {
                $missingColumns[] = $column;
                continue;
            }

            if ($this->checkCondition(['input_column_value' => $input[$column], 'input' => $input], $conditions)) {
                $this->addValueToAggregationGroup(
                    $column,
                    $input,
                    $this->getOption($state, 'reference_key'),
                    $this->getOption($state, 'aggregation_key')
                );
            }
        }

        if (!empty($missingColumns)) {
            $colStr = implode(', ', $missingColumns);
            $message = "Missing columns [{$colStr}] in input";

            if ($this->getOption($state, 'ignore_missing')) {
                $this->logger->warning($message);
            } else {
                throw new \UnexpectedValueException($message);
            }
        }
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        $state->setOutput($this->result);
    }

    /**
     * @param string $column
     * @param mixed  $input
     * @param string $referenceKey
     * @param string $aggregationKey
     */
    protected function addValueToAggregationGroup($column, $input, $referenceKey, $aggregationKey)
    {
        if (!isset($this->result[$column])) {
            $this->result[$column] = [
                $referenceKey => $column,
                $aggregationKey => [],
            ];
        }

        $this->result[$column][$aggregationKey][] = $input;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('columns');
        $resolver->setAllowedTypes('columns', 'array');

        $resolver->setDefault('reference_key', 'column');
        $resolver->setAllowedTypes('reference_key', 'string');

        $resolver->setDefault('aggregation_key', 'values');
        $resolver->setAllowedTypes('aggregation_key', 'string');

        $this->configureWrappedConditionOptions('condition', $resolver);

        $resolver->setDefault('ignore_missing', false);
        $resolver->setAllowedTypes('ignore_missing', 'boolean');
    }
}
