<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File\Csv;

use CleverAge\ProcessBundle\Filesystem\CsvFile;
use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split long CSV files into smaller ones, keeping the headers
 */
class CsvSplitterTask extends InputCsvReaderTask
{
    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws IOException
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        if (null === $this->csv) {
            $headers = $this->getHeaders($state, $options);
            $csv = new CsvFile(
                $options['file_path'],
                $options['delimiter'],
                $options['enclosure'],
                $options['escape'],
                $headers,
                $options['mode']
            );

            if ($csv->getLineCount() > $options['max_lines']) {
                $this->logger->debug("Found big CSV file ({$csv->getLineCount()} lines), splitting...");
            }
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
     * @param ProcessState $state
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function next(ProcessState $state)
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

    /**
     * @param ProcessState $state
     *
     * @throws IOException
     */
    public function finalize(ProcessState $state)
    {
        if ($this->csv instanceof CsvResource) {
            $this->csv->close();
            $this->csv = null;
        }
    }

    /**
     * @param CsvFile $csv
     * @param int     $maxLines
     *
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    protected function splitCsv(CsvFile $csv, $maxLines)
    {
        $tmpFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'php_'.uniqid('process', false).'.csv';
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

        while ($splitCsv->getLineNumber() < $maxLines && !$csv->isEndOfFile()) {
            $raw = $csv->readRaw();
            if (false === $raw) {
                continue; // This is probably an empty line, no harm to skip it
            }
            $splitCsv->writeRaw($raw);
        }
        $splitCsv->close();

        return $tmpFilePath;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'max_lines' => 1000,
            ]
        );
    }
}
