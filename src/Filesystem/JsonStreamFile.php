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
 * Wrapper around JSON files to read them in a stream.
 */
class JsonStreamFile implements FileStreamInterface, WritableFileInterface
{
    protected \SplFileObject $file;

    private readonly int $jsonFlags;

    protected ?int $lineCount = null;

    protected int $lineNumber = 1;

    public function __construct(
        string $filename,
        string $mode = 'rb',
        ?array $splFileObjectFlags = null,
        ?array $jsonFlags = null,
    ) {
        $this->file = new \SplFileObject($filename, $mode);

        // Useful to skip empty trailing lines (doesn't work well on PHP 8, see readLine() code)
        $this->file->setFlags(null !== $splFileObjectFlags
            ? array_sum($splFileObjectFlags)
            : \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY
        );

        $this->jsonFlags = null !== $jsonFlags
            ? array_sum($jsonFlags)
            : \JSON_THROW_ON_ERROR
        ;
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
    public function readLine(?int $length = null): ?array
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

        return json_decode($rawLine, true, 512, $this->jsonFlags);
    }

    public function writeLine(array $fields): int
    {
        $this->file->fwrite(json_encode($fields, $this->jsonFlags).\PHP_EOL);
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
