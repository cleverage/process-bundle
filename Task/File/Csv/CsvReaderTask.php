<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
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
 * Iterates over a CSV file.
 *
 *
 * Reads a CSV file and iterate on each line, returning an array of key -> values. Skips empty lines. Ignores any input.
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask`
 *  * **Iterable task**
 *  * **Input**: _ignored_
 *  * **Output**: `array`, foreach line, it will return a php array where key comes from headers and values are strings.
 * Underlying method is [fgetcsv](https://secure.php.net/manual/en/function.fgetcsv.php).
 *
 * ##### Options
 *
 * * `file_path` (`string`, _required_): Path of the file to read from (relative to symfony root or absolute)
 * * `delimiter` (`string`, _defaults to_ `;`): CSV delimiter
 * * `enclosure` (`string`, _defaults to_ `"`): CSV enclosure character
 * * `escape` (`string`, _defaults to_ `\\`): CSV escape character
 * * `headers` (`array|null`, _defaults to_ `null`): Static list of CSV headers, without the option, it will be dynamically read from first input
 * * `mode` (`string`, _defaults to_ `rb`): File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php))
 * * `log_empty_lines` (`bool`, _defaults to_ `false`): Log when the output is empty
 *
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvReaderTask extends AbstractCsvTask implements IterableTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
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
     * {@inheritDoc}
     *
     * @internal
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
