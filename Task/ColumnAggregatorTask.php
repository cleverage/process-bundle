<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
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
 * @todo   @vclavreul describe this task
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
