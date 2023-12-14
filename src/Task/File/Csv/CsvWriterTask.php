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
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the file path from configuration and iterates over it
 * Ignores any input.
 *
 * @property CsvFile $csv
 */
class CsvWriterTask extends AbstractCsvTask implements BlockingTaskInterface
{
    public function execute(ProcessState $state): void
    {
        if (!$this->csv instanceof CsvFile) {
            $this->initFile($state);
            if ($this->getOption($state, 'write_headers') && 0 === filesize($this->csv->getFilePath())) {
                $this->csv->writeHeaders();
            }
        }
        $this->csv->writeLine($this->getInput($state));
    }

    public function proceed(ProcessState $state): void
    {
        $state->setOutput($this->csv->getFilePath());
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'mode' => 'wb',
            'split_character' => '|',
            'write_headers' => true,
        ]);

        $resolver->setNormalizer(
            'file_path',
            static fn (Options $options, $value): string => strtr(
                $value,
                [
                    '{date}' => date('Ymd'),
                    '{date_time}' => date('Ymd_His'),
                    '{unique_token}' => uniqid('', true),
                ]
            )
        );
    }

    protected function getInput(ProcessState $state): array
    {
        $input = $state->getInput();
        if (!\is_array($input)) {
            throw new \UnexpectedValueException('Input value is not an array');
        }
        $splitCharacter = $this->getOption($state, 'split_character');

        foreach ($input as &$item) {
            if (\is_array($item)) {
                $item = implode($splitCharacter, $item);
            }
        }

        return $input;
    }

    protected function getHeaders(ProcessState $state, array $options): ?array
    {
        $headers = $options['headers'];
        if (null === $headers) {
            $headers = array_keys($state->getInput());
        }

        return $headers;
    }
}
