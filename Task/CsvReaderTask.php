<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Filesystem\CsvFile;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvReaderTask extends AbstractCsvTask implements IterableTaskInterface
{
    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $output = null;
        try {
            if (!$this->csv instanceof CsvFile) {
                $this->initFile($state);
            }
            $output = $this->csv->readLine();
        } catch (\Exception $e) {
            $state->stop($e);

            return;
        }

        if (null === $output) {
            $state->stop(new \UnexpectedValueException("CSV file {$this->csv->getFilePath()} is empty or unreadable"));
        }
        $state->addErrorContextValue('csv_file', $this->csv->getFilePath());
        $state->addErrorContextValue('csv_line', $this->csv->getCurrentLine());
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
}
