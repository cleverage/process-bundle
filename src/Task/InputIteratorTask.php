<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use ArrayIterator;
use CleverAge\ProcessBundle\Model\ProcessState;
use Iterator;
use IteratorAggregate;
use UnexpectedValueException;
use function is_array;

/**
 * Iterates from the input of the previous task
 */
class InputIteratorTask extends AbstractIterableOutputTask
{
    protected function initializeIterator(ProcessState $state): Iterator
    {
        $input = $state->getInput();
        if ($input instanceof Iterator) {
            return $input;
        }
        if ($input instanceof IteratorAggregate) {
            return $input->getIterator();
        }
        if (is_array($input)) {
            return new ArrayIterator($input);
        }

        throw new UnexpectedValueException('Cannot create iterator from input');
    }
}
