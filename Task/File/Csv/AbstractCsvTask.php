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

use CleverAge\ProcessBundle\Filesystem\CsvFile;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input
 */
abstract class AbstractCsvTask extends AbstractCsvResourceTask
{
    protected function initFile(ProcessState $state)
    {
        if ($this->csv) {
            return;
        }
        $options = $this->getOptions($state);
        $headers = $this->getHeaders($state, $options);
        $this->csv = new CsvFile(
            $options['file_path'],
            $options['delimiter'],
            $options['enclosure'],
            $options['escape'],
            $headers,
            $options['mode']
        );
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setDefaults([
            'mode' => 'rb',
        ]);
        $resolver->setAllowedTypes('mode', ['string']);
    }
}
