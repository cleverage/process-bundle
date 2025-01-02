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

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads an input file line by line and outputs each line.
 */
class InputLineReaderTask extends LineReaderTask
{
    protected function getOptions(ProcessState $state): array
    {
        $options = parent::getOptions($state);
        if (null !== $state->getInput()) {
            $options['filename'] = $state->getInput();
        }

        return $options;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->remove('filename');
    }
}
