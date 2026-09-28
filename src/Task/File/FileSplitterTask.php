<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Filesystem\SplFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Split long file into smaller ones.
 */
class FileSplitterTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected ?SplFile $file = null;

    /**
     * Next line of the source file to write (read ahead to detect the end of the file), null when there is none.
     */
    private ?string $nextLine = null;

    public function execute(ProcessState $state): void
    {
        $options = $this->getMergedOptions($state);
        if (!$this->file instanceof SplFile) {
            // No flag: lines are read with fgets(), which ignores DROP_NEW_LINE/SKIP_EMPTY and must not be preceded
            // by a rewind() in READ_AHEAD mode (the first line would be skipped)
            $this->file = new SplFile($options['file_path'], 'rb', []);
            $this->nextLine = $this->file->readLine();
        }

        if (null === $this->nextLine) {
            // Empty source file: nothing to split
            $this->file = null;
            $state->setSkipped(true);

            return;
        }

        // Return a temporary file containing a limited number of lines
        $splittedFilename = $this->splitFile($this->file, $options['max_lines']);
        $state->setOutput($splittedFilename);
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration.
     */
    public function next(ProcessState $state): bool
    {
        if (!$this->file instanceof SplFile) {
            return false;
        }

        if (null === $this->nextLine) {
            $this->file = null;

            return false;
        }

        return true;
    }

    protected function splitFile(SplFile $file, int $maxLines): string
    {
        $tmpFilePath = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'php_'.uniqid('process', false).'.tmp';
        $splitFile = new SplFile($tmpFilePath, 'wb', []);

        $writtenLines = 0;
        while (null !== $this->nextLine && $writtenLines < $maxLines) {
            // fgets() keeps the line break while writeLine() appends one
            $splitFile->writeLine($this->stripLineBreak($this->nextLine));
            ++$writtenLines;
            $this->nextLine = $file->readLine();
        }

        return $tmpFilePath;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setDefaults([
            'max_lines' => 1000,
        ]);
        $resolver->setAllowedTypes('max_lines', ['int']);
    }

    /**
     * @return array<mixed>
     */
    protected function getMergedOptions(ProcessState $state): array
    {
        /** @var array<mixed> $options */
        $options = $this->getOptions($state);

        /** @var array<mixed>|mixed $input */
        $input = $state->getInput() ?: [];
        if (!\is_array($input)) {
            $input = [];
        }
        // @var array<mixed> $input

        return array_merge($options, $input);
    }

    private function stripLineBreak(string $line): string
    {
        if (str_ends_with($line, "\r\n")) {
            return substr($line, 0, -2);
        }
        if (str_ends_with($line, "\n")) {
            return substr($line, 0, -1);
        }

        return $line;
    }
}
