<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split a single line into multiple lines based on multiple columns and split characters
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class SplitJoinLineTask extends AbstractIterableOutputTask
{
    /**
     * {@inheritdoc}
     */
    public function next(ProcessState $state): bool
    {
        $valid = parent::next($state);
        if (!$valid) {
            $this->iterator = null;
        }

        return $valid;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'split_columns',
                'join_column',
            ]
        );
        $resolver->setAllowedTypes('split_columns', ['array']);
        $resolver->setAllowedTypes('join_column', ['string']);
        $resolver->setDefaults(
            [
                'split_character' => ',',
            ]
        );
    }

    /**
     * @param ProcessState $state
     *
     * @return \Iterator
     */
    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $originalLine = $state->getInput();
        $options = $this->getOptions($state);

        $lineCopy = $originalLine;
        foreach ($options['split_columns'] as $splitColumn) {
            unset($lineCopy[$splitColumn]);
        }

        $outputLines = [];
        foreach ($options['split_columns'] as $column) {
            if (!array_key_exists($column, $originalLine)) {
                throw new \UnexpectedValueException("Missing column {$column}");
            }
            $columnValues = explode($options['split_character'], $originalLine[$column]);
            foreach ($columnValues as $columnValue) {
                $outputLine = $lineCopy;
                $outputLine[$options['join_column']] = $columnValue;
                $outputLines[] = $outputLine;
            }
        }

        return new \ArrayIterator($outputLines);
    }
}
