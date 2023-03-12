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
use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\ProcessState;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split long CSV files into smaller ones, keeping the headers
 */
class CsvSplitterTask extends InputCsvReaderTask
{
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        if ($this->csv === null) {
            $headers = $this->getHeaders($state, $options);
            $csv = new CsvFile(
                $options['file_path'],
                $options['delimiter'],
                $options['enclosure'],
                $options['escape'],
                $headers,
                $options['mode']
            );

            $this->csv = $csv;
        }

        // Return a temporary file containing a limited number of lines
        $state->setOutput($this->splitCsv($this->csv, $options['max_lines']));
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     *
     * @return bool
     */
    public function next(ProcessState $state)
    {
        if (! $this->csv instanceof CsvResource) {
            return false;
        }

        $endOfFile = $this->csv->isEndOfFile();
        if ($endOfFile) {
            $this->csv->close();
            $this->csv = null;
        }

        return ! $endOfFile;
    }

    public function finalize(ProcessState $state): void
    {
        if ($this->csv instanceof CsvResource) {
            $this->csv->close();
            $this->csv = null;
        }
    }

    /**
     * @param int     $maxLines
     *
     * @return string
     */
    protected function splitCsv(CsvFile $csv, $maxLines)
    {
        $tmpFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_' . uniqid('process', false) . '.csv';
        $tmpFile = fopen($tmpFilePath, 'wb+');
        if ($tmpFile === false) {
            throw new RuntimeException("Unable to open temporary file {$tmpFilePath}");
        }
        $splitCsv = new CsvResource(
            $tmpFile,
            $csv->getDelimiter(),
            $csv->getEnclosure(),
            $csv->getEscape(),
            $csv->getHeaders()
        );
        $splitCsv->writeHeaders();

        while ($splitCsv->getLineNumber() < $maxLines && ! $csv->isEndOfFile()) {
            $raw = $csv->readRaw();
            if ($raw === false) {
                continue; // This is probably an empty line, no harm to skip it
            }
            $splitCsv->writeRaw($raw);
        }
        $splitCsv->close();

        return $tmpFilePath;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'max_lines' => 1000,
        ]);
    }
}
