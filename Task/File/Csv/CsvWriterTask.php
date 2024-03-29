<?php declare(strict_types=1);
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
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 *
 * @property CsvFile $csv
 */
class CsvWriterTask extends AbstractCsvTask implements BlockingTaskInterface
{
    /**
     * @param ProcessState $state
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        if (!$this->csv instanceof CsvFile) {
            $this->initFile($state);
            if ($this->getOption($state, 'write_headers') && 0 === filesize($this->csv->getFilePath())) {
                $this->csv->writeHeaders();
            }
        }
        $this->csv->writeLine($this->getInput($state));
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
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'mode' => 'wb',
                'split_character' => '|',
                'write_headers' => true,
            ]
        );

        $resolver->setNormalizer(
            'file_path',
            static function (Options $options, $value) {
                $value = strtr(
                    $value,
                    [
                        '{date}' => date('Ymd'),
                        '{date_time}' => date('Ymd_His'),
                        '{unique_token}' => uniqid(),
                    ]
                );

                return $value;
            }
        );
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws ExceptionInterface
     *
     * @return array
     */
    protected function getInput(ProcessState $state)
    {
        $input = $state->getInput();
        if (!\is_array($input)) {
            throw new \UnexpectedValueException('Input value is not an array');
        }
        $splitCharacter = $this->getOption($state, 'split_character');

        /** @var array $input */
        foreach ($input as $key => &$item) {
            if (\is_array($item)) {
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
