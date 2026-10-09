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
 * Reads the whole input file and outputs its content.
 */
class InputFileReaderTask extends FileReaderTask
{
    #[\Override]
    public function initialize(ProcessState $state): void
    {
        // Only validate the options: the file path comes from the input
        parent::getOptions($state);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function getOptions(ProcessState $state): array
    {
        $options = parent::getOptions($state);
        $filename = $state->getInput();
        if (!\is_string($filename) || '' === $filename) {
            throw new \UnexpectedValueException('No file path given as input');
        }
        $options['filename'] = $filename;

        return $options;
    }

    #[\Override]
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->remove('filename');
    }
}
