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
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvWriterTask extends AbstractCsvTask implements BlockingTaskInterface
{
    /**
     * @param ProcessState $processState
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $processState)
    {
        try {
            if (!$this->csv instanceof CsvFile) {
                $this->initFile($processState);
                $this->csv->writeHeaders();
            }
            $this->csv->writeLine($this->getInput($processState));
        } catch (\Exception $e) {
            $processState->stop($e);
        }
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
                'mode' => 'w',
                'split_character' => '|',
            ]
        );
    }

    /**
     * @param ProcessState $processState
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return array
     */
    protected function getInput(ProcessState $processState)
    {
        $input = $processState->getInput();
        if (!is_array($input)) {
            throw new \UnexpectedValueException('Input value is not an array');
        }
        $options = $this->getOptions($processState);

        /** @var array $input */
        foreach ($input as $key => &$item) {
            if (is_array($item)) {
                $item = implode($options['split_character'], $item);
            }
        }

        return $input;
    }

    /**
     * @param ProcessState $processState
     */
    public function proceed(ProcessState $processState)
    {
        $processState->setOutput($this->csv->getFilePath());
    }
}
