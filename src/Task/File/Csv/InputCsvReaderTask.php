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

namespace CleverAge\ProcessBundle\Task\File\Csv;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the filepath from the input
 */
class InputCsvReaderTask extends CsvReaderTask
{
    protected function getOptions(ProcessState $state): array
    {
        $options = parent::getOptions($state);
        if ($state->getInput() !== null) {
            $options['file_path'] = $this->getFilePath($options, $state->getInput());
        }

        return $options;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->remove('file_path');

        // If there is no base_path, then the given path should be absolute
        $resolver->setDefault('base_path', '');
        $resolver->setAllowedTypes('base_path', 'string');
    }

    /**
     * If there is no base_path, then the given path from input should be absolute
     */
    protected function getFilePath(array $options, string $input): string
    {
        $basePath = $options['base_path'];
        if ($basePath !== '') {
            $basePath = rtrim((string) $options['base_path'], '/') . '/';
        }

        return $basePath . $input;
    }
}
