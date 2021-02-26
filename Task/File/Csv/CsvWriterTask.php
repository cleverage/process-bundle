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
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Writes all incoming data to a CSV file, outputting it's path when over
 *
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask`
 *  * **Blocking task**
 *  * **Input**: `array`, foreach line, it will need a php array where key match the headers and values are convertible to string.
 * Underlying method is [fputcsv](https://secure.php.net/manual/en/function.fputcsv.php).
 *  * **Output**: `string`, absolute path of the produced file
 *
 * ##### Options
 *
 * * `file_path` (`string`, _required_): Path of the file to write to (relative to symfony root or absolute). It can also take two placeholders (`{date}` and `{date_time}`) to insert timestamps into the filename
 * * `delimiter` (`string`, _defaults to_ `;`): CSV delimiter
 * * `enclosure` (`string`, _defaults to_ `"`): CSV enclosure character
 * * `escape` (`string`, _defaults to_ `\\`): CSV escape character
 * * `headers` (`array|null`, _defaults to_ `null`): `null` | Static list of CSV headers, without the option, it will be dynamically read from first line
 * * `mode` (`string`, _defaults to_ `wb`): File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php))
 * * `split_character` (`string`, _defaults to_ `\|`): Used to implode array values
 * * `write_headers` (`bool`, _defaults to_ `true`): Write the headers as a first line
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvWriterTask extends AbstractCsvTask implements BlockingTaskInterface
{
    /**
     * {@inheritDoc}
     *
     * @internal
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
     * {@inheritDoc}
     *
     * @internal
     */
    public function proceed(ProcessState $state)
    {
        if ($this->csv) {
            $state->setOutput($this->csv->getFilePath());
        }
    }

    /**
     * {@inheritDoc}
     *
     * @internal
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
