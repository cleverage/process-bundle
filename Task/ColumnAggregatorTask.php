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


use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Transformer\ConditionTrait;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ColumnAggregatorTask extends AbstractConfigurableTask implements BlockingTaskInterface
{

    use ConditionTrait;

    /**
     * @var array
     */
    protected $result = [];


    /**
     * ColumnAggregatorTask constructor.
     *
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        $columns = $this->getOption($state, 'columns');
        $conditions = $this->getOption($state, 'condition');

        foreach ($columns as $column) {
            if (!isset($input[$column])) {
                throw new \UnexpectedValueException("Missing column '{$column}' in input");
            }

            if ($this->checkCondition(['input_column_value' => $input[$column], 'input' => $input], $conditions)) {
                $this->addValueToAggregationGroup($column, $input, $this->getOption($state, 'reference_key'), $this->getOption($state, 'aggregation_key'));
            }
        }
    }

    public function proceed(ProcessState $state)
    {
        $state->setOutput($this->result);
    }

    protected function addValueToAggregationGroup($column, $input, $referenceKey, $aggregationKey)
    {
        if (!isset($this->result[$column])) {
            $this->result[$column] = [
                $referenceKey   => $column,
                $aggregationKey => [],
            ];
        }

        $this->result[$column][$aggregationKey][] = $input;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('columns');
        $resolver->setAllowedTypes('columns', 'array');

        $resolver->setDefault('reference_key', 'column');
        $resolver->setAllowedTypes('reference_key', 'string');

        $resolver->setDefault('aggregation_key', 'values');
        $resolver->setAllowedTypes('aggregation_key', 'string');

        $this->configureWrappedConditionOptions('condition', $resolver);
    }


}
