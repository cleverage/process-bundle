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

namespace CleverAge\ProcessBundle\Task\File\Csv;

use CleverAge\ProcessBundle\Filesystem\CsvFile;
use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split long CSV files into smaller ones, keeping the headers.
 */
class CsvSplitterTask extends InputCsvReaderTask
{
    /**
     * Number of data lines written in the last produced file.
     */
    protected int $splitLineCount = 0;

    #[\Override]
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        if (!$this->csv instanceof CsvResource) {
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
        $splitFilePath = $this->splitCsv($this->csv, $options['max_lines']);
        if (0 === $this->splitLineCount) {
            // The end of the source file is only detected after trying to read past its last line: no empty file
            unlink($splitFilePath);
            $state->setSkipped(true);

            return;
        }
        $state->setOutput($splitFilePath);
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration.
     */
    #[\Override]
    public function next(ProcessState $state): bool
    {
        if (!$this->csv instanceof CsvResource) {
            return false;
        }

        $endOfFile = $this->csv->isEndOfFile();
        if ($endOfFile) {
            $this->csv->close();
            $this->csv = null;
        }

        return !$endOfFile;
    }

    #[\Override]
    public function finalize(ProcessState $state): void
    {
        if ($this->csv instanceof CsvResource) {
            $this->csv->close();
            $this->csv = null;
        }
    }

    protected function splitCsv(CsvResource $csv, int $maxLines): string
    {
        $tmpFilePath = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'php_'.uniqid('process', false).'.csv';
        $tmpFile = fopen($tmpFilePath, 'wb+');
        if (false === $tmpFile) {
            throw new \RuntimeException("Unable to open temporary file {$tmpFilePath}");
        }
        $splitCsv = new CsvResource(
            $tmpFile,
            $csv->getDelimiter(),
            $csv->getEnclosure(),
            $csv->getEscape(),
            $csv->getHeaders()
        );
        $splitCsv->writeHeaders();

        $this->splitLineCount = 0;
        while ($this->splitLineCount < $maxLines && !$csv->isEndOfFile()) {
            $raw = $csv->readRaw();
            if (false === $raw) {
                continue; // This is probably an empty line, no harm to skip it
            }
            $splitCsv->writeRaw($raw);
            ++$this->splitLineCount;
        }
        $splitCsv->close();

        return $tmpFilePath;
    }

    #[\Override]
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'max_lines' => 1000,
        ]);
        $resolver->setAllowedTypes('max_lines', ['int']);
        $resolver->setAllowedValues('max_lines', static fn (int $value): bool => $value >= 1);
    }
}
