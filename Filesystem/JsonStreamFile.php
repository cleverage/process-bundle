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

namespace CleverAge\ProcessBundle\Filesystem;

use SplFileObject;

/**
 * Wrapper around JSON files to read them in a stream
 */
class JsonStreamFile implements FileStreamInterface, WritableFileInterface
{
    protected SplFileObject $file;

    /**
     * @var int
     */
    protected $lineCount;

    /**
     * @var int
     */
    protected $lineNumber = 1;

    /**
     * JsonStreamFile constructor.
     *
     * @param string $mode
     */
    public function __construct(string $filename, $mode = 'rb')
    {
        $this->file = new SplFileObject($filename, $mode);

        // Useful to skip empty trailing lines
        $this->file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
    }

    /**
     * Warning! This method will rewind the file to the beginning before and after counting the lines!
     */
    public function getLineCount(): int
    {
        if ($this->lineCount === null) {
            $this->rewind();
            $line = 0;
            while (! $this->isEndOfFile()) {
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
     * Return an array containing current data and moving the file pointer
     *
     * @param null $length
     */
    public function readLine($length = null): ?array
    {
        if ($this->isEndOfFile()) {
            return null;
        }

        $rawLine = $this->file->fgets();
        $this->lineNumber++;

        return json_decode($rawLine, true, 512, JSON_THROW_ON_ERROR);
    }

    public function writeLine(array $item): int
    {
        $this->file->fwrite(json_encode($item, JSON_THROW_ON_ERROR) . PHP_EOL);
        $this->lineNumber++;

        return $this->lineNumber;
    }

    /**
     * Rewind data to array
     */
    public function rewind(): void
    {
        $this->file->rewind();
        $this->lineNumber = 1;
    }
}
