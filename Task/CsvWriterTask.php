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
use Psr\Log\LogLevel;
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
    /** @var CsvFile */
    protected $csv;

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        try {
            if (!$this->csv instanceof CsvFile) {
                $this->initFile($state);
                if (0 === filesize($this->csv->getFilePath())) {
                    $this->csv->writeHeaders();
                }
            }
            $this->csv->writeLine($this->getInput($state));
        } catch (\Exception $e) {
            $options = $this->getOptions($state);

            $state->setError($state->getInput());
            if ($options[self::LOG_ERRORS]) {
                $state->log('CSV Writer Exception: '.$e->getMessage(), LogLevel::ERROR);
            }
            if ($options[self::ERROR_STRATEGY] === self::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($options[self::ERROR_STRATEGY] === self::STRATEGY_STOP) {
                $state->stop($e);
            }
        }
    }

    /**
     * @param ProcessState $state
     */
    public function proceed(ProcessState $state)
    {
        if ($this->csv) {
            $state->setOutput($this->csv->getFilePath());
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
                'mode'            => 'w',
                'split_character' => '|',
            ]
        );
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return array
     */
    protected function getInput(ProcessState $state)
    {
        $input = $state->getInput();
        if (!is_array($input)) {
            throw new \UnexpectedValueException('Input value is not an array');
        }
        $splitCharacter = $this->getOption($state, 'split_character');

        /** @var array $input */
        foreach ($input as $key => &$item) {
            if (is_array($item)) {
                $item = implode($splitCharacter, $item);
            }
        }

        return $input;
    }

    /**
     * @param ProcessState $state
     * @param array        $options
     *
     * @return array
     */
    protected function getHeaders(ProcessState $state, array $options)
    {
        $headers = $options['headers'];
        if (null === $headers) {
            $headers = array_keys($state->getInput());
        }

        return $headers;
    }
}
