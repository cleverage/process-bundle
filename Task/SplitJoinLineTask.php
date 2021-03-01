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

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split a single line into multiple lines based on multiple columns and split characters.
 *
 * For each input, it will take each value from columns referenced by `split_columns`, split it with `split_character`,
 * and for each part, produce an output item that will contain all data from input + a column with the key from `join_column` containing the current part.
 *
 * This might produce more output than input, with semi-duplicated lines.
 *
 * ##### Task or Transformer reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Task\SplitJoinLineTask`
 * * **Iterable task**
 * * **Input**: `array`, description
 * * **Output**: `type`, description
 *
 * ##### Options
 *
 * * `split_columns` (`array`, _required_): the list of properties to split
 * * `join_column` (`string`, _required_): the name of the property that will be included in the output, containing a part of a split value
 * * `split_character` (`string`, _defaults to_ `,`): the delimiter for split values
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class SplitJoinLineTask extends AbstractIterableOutputTask
{
    /**
     * {@inheritDoc}
     * @internal
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
     * {@inheritDoc}
     * @internal
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
     * {@inheritDoc}
     * @internal
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
