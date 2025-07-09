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

    private ?array $splFileObjectFlags = null;

    private int $lineCount;

    public function execute(ProcessState $state): void
    {
        $options = $this->getMergedOptions($state);
        $this->splFileObjectFlags = [\SplFileObject::READ_AHEAD, \SplFileObject::SKIP_EMPTY];
        if (!$this->file instanceof SplFile) {
            $this->file = new SplFile($options['file_path'], 'rb', $this->splFileObjectFlags);
            $this->lineCount = $this->file->getLineCount();
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

        // Fix issue on PHP 8 with empty line at the end, even if SKIP_EMPTY is set
        $endOfFile = $this->file->isEndOfFile() || $this->file->getLineNumber() > $this->lineCount;
        if ($endOfFile) {
            $this->file = null;
        }

        return !$endOfFile;
    }

    protected function splitFile(SplFile $file, int $maxLines): string
    {
        $tmpFilePath = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'php_'.uniqid('process', false).'.tmp';
        $splitFile = new SplFile($tmpFilePath, 'wb', $this->splFileObjectFlags);

        while ($splitFile->getLineNumber() <= $maxLines && !$file->isEndOfFile()) {
            $line = $file->readLine();
            if ('' === $line || null === $line) {
                continue; // This is probably an empty line, no harm to skip it
            }
            $splitFile->writeLine($line);
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
}
