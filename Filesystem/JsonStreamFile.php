<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Wrapper around JSON files to read them in a stream
 */
class JsonStreamFile implements FileStreamInterface
{
    /** @var \SplFileObject */
    protected $file;

    /** @var int */
    protected $lineCount;

    /** @var int */
    protected $lineNumber = 1;

    /**
     * JsonStreamFile constructor.
     *
     * @param string $filename
     * @param string $mode
     */
    public function __construct(string $filename, $mode = 'rb')
    {
        $this->file = new \SplFileObject($filename, $mode);

        // Useful to skip empty trailing lines
        $this->file->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
    }

    /**
     * Warning! This method will rewind the file to the beginning before and after counting the lines!
     *
     * @throws \RuntimeException
     *
     * @return int
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

    /**
     * {@inheritDoc}
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * @return bool
     */
    public function isEndOfFile(): bool
    {
        return $this->file->eof();
    }

    /**
     * Return an array containing current data and moving the file pointer
     *
     * @param null $length
     *
     * @return array|null
     */
    public function readLine($length = null): ?array
    {
        if ($this->isEndOfFile()) {
            return null;
        }

        $rawLine = $this->file->fgets();
        $this->lineNumber++;

        return json_decode($rawLine, true);
    }

    /**
     * @param array $item
     *
     * @return int
     */
    public function writeLine(array $item): int
    {
        $this->file->fwrite(json_encode($item).PHP_EOL);
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
