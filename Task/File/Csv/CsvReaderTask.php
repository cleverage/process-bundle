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
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvReaderTask extends AbstractCsvTask implements IterableTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws ExceptionInterface
     * @throws \LogicException
     */
    public function execute(ProcessState $state)
    {
        $output = null;
        if ($this->csv instanceof CsvFile
            && $this->csv->getFilePath() !== $this->getOption($state, 'file_path')) {
            $this->csv = null;
        }

        if (!$this->csv instanceof CsvFile) {
            $this->initFile($state);
        }
        $lineNumber = $this->csv->getLineNumber();
        $output = $this->csv->readLine();

        if (null === $output) {
            if ($this->getOption($state, 'log_empty_lines')) {
                $logContext = [
                    'csv_file' => $this->csv->getFilePath(),
                    'csv_line' => $lineNumber,
                ];
                $this->logger->warning("Empty line detected at line: {$lineNumber}", $logContext);
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
        if (!$this->csv instanceof CsvFile) {
            throw new \LogicException('No CSV File initialized');
        }

        $state->removeErrorContext('csv_file');
        $state->removeErrorContext('csv_line');

        return !$this->csv->isEndOfFile();
    }

    /**
     * @param ProcessState $state
     * @param array        $options
     *
     * @return array
     */
    protected function getHeaders(ProcessState $state, array $options)
    {
        return $options['headers'];
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'log_empty_lines' => false,
            ]
        );
    }
}
