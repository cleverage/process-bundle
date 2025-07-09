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

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Wrapper around files to read/write them.
 */
class SplFile
{
    protected \SplFileObject $file;

    protected ?int $lineCount = null;

    protected int $lineNumber = 1;

    public function __construct(
        string $filename,
        string $mode = 'rb',
        ?array $splFileObjectFlags = null,
    ) {
        $this->file = new \SplFileObject($filename, $mode);

        // Useful to skip empty trailing lines (doesn't work well on PHP 8, see readLine() code)
        $this->file->setFlags(null !== $splFileObjectFlags
            ? array_sum($splFileObjectFlags)
            : \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY
        );
    }

    /**
     * Warning! This method will rewind the file to the beginning before and after counting the lines!
     */
    public function getLineCount(): int
    {
        if (null === $this->lineCount) {
            $this->rewind();
            $line = 0;
            while (!$this->isEndOfFile()) {
                ++$line;
                $this->file->next();
            }
            $this->rewind();

            $this->lineCount = $line;
        }

        return $this->lineCount;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function isEndOfFile(): bool
    {
        return $this->file->eof();
    }

    /**
     * Return an array containing current data and moving the file pointer.
     */
    public function readLine(?int $length = null): ?string
    {
        if ($this->isEndOfFile()) {
            return null;
        }

        $rawLine = $this->file->fgets();
        // Fix issue on PHP 8 with empty line at the end, even if SKIP_EMPTY is set
        if ('' === $rawLine) {
            return null;
        }
        ++$this->lineNumber;

        return $rawLine;
    }

    public function writeLine(string $data): int
    {
        $this->file->fwrite($data.\PHP_EOL);
        ++$this->lineNumber;

        return $this->lineNumber;
    }

    /**
     * Rewind data to array.
     */
    public function rewind(): void
    {
        $this->file->rewind();
        $this->lineNumber = 1;
    }
}
