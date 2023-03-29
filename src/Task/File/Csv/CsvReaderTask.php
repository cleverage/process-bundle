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
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input
 */
class CsvReaderTask extends AbstractCsvTask implements IterableTaskInterface
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function execute(ProcessState $state): void
    {
        if ($this->csv instanceof CsvFile
            && $this->csv->getFilePath() !== $this->getOption($state, 'file_path')) {
            $this->csv = null;
        }

        if (! $this->csv instanceof CsvFile) {
            $this->initFile($state);
        }
        $lineNumber = $this->csv->getLineNumber();
        $output = $this->csv->readLine();

        if ($output === null) {
            if ($this->getOption($state, 'log_empty_lines')) {
                $logContext = [
                    'csv_file' => $this->csv->getFilePath(),
                    'csv_line' => $lineNumber,
                ];
                $this->logger->warning("Empty line detected at line: $lineNumber", $logContext);
            }

            $state->setSkipped(true);
        }

        $state->addErrorContextValue('csv_file', $this->csv->getFilePath());
        $state->addErrorContextValue('csv_line', $lineNumber);
        $state->setOutput($output);
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     */
    public function next(ProcessState $state): bool
    {
        if (! $this->csv instanceof CsvFile) {
            throw new LogicException('No CSV File initialized');
        }

        $state->removeErrorContext('csv_file');
        $state->removeErrorContext('csv_line');

        return ! $this->csv->isEndOfFile();
    }

    protected function getHeaders(ProcessState $state, array $options): array
    {
        return $options['headers'];
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'log_empty_lines' => false,
        ]);
    }
}
