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
     * @param ProcessState $processState
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $processState)
    {
        $output = null;
        try {
            if (!$this->csv instanceof CsvFile) {
                $this->initFile($processState);
            }
            $output = $this->csv->readLine();
        } catch (\Exception $e) {
            $processState->stop($e);
            return;
        }

        if (null === $output) {
            $processState->stop(new \UnexpectedValueException("CSV file {$this->csv} is empty or unreadable"));
        }
        $processState->setOutput($output);
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     *
     * @param ProcessState $processState
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function next(ProcessState $processState)
    {
        if (!$this->csv instanceof CsvFile) {
            throw new \LogicException('No CSV File initialized');
        }

        return !$this->csv->isEndOfFile();
    }
}
