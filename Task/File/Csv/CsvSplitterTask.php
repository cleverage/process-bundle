<?php

namespace CleverAge\ProcessBundle\Task\File\Csv;

use CleverAge\ProcessBundle\Filesystem\CsvFile;
use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split long CSV files into smaller ones, keeping the headers
 */
class CsvSplitterTask extends InputCsvReaderTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\Filesystem\Exception\IOException
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

            $output = $state->getConsoleOutput();
            if ($csv->getLineCount() > $options['max_lines'] && $output) {
                $output->writeln("<info>Found big CSV file ({$csv->getLineCount()} lines), splitting...</info>");
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
     * @throws \Symfony\Component\Filesystem\Exception\IOException
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
        $tmpFile = fopen($tmpFilePath, 'w+');
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

        while ($splitCsv->getCurrentLine() < $maxLines && !$csv->isEndOfFile()) {
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
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
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
