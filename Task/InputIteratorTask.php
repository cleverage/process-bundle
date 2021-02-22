<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * Iterates from the input of the previous task
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class InputIteratorTask extends AbstractIterableOutputTask
{
    /**
     * @inheritDoc
     */
    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $input = $state->getInput();
        if ($input instanceof \Iterator) {
            return $input;
        }
        if ($input instanceof \IteratorAggregate) {
            return $input->getIterator();
        }
        if (\is_array($input)) {
            return new \ArrayIterator($input);
        }

        throw new \UnexpectedValueException('Cannot create iterator from input');
    }
}
