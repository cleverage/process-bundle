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

use LogicException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Read and write CSV resources through a simple API.
 */
class CsvResource implements WritableStructuredFileInterface, SeekableFileInterface
{
    /**
     * @var resource
     */
    protected $handler;

    /**
     * @var int|null
     */
    protected $lineCount;

    protected array $headers;

    /**
     * @var bool
     */
    protected $manualHeaders = false;

    protected int $headerCount;

    /**
     * @var int
     */
    protected $lineNumber = 1;

    protected bool $closed;

    /**
     * @var bool
     */
    protected $seekCalled = false;

    /**
     * @param resource $resource
     * @param string   $delimiter CSV delimiter
     * @param string   $enclosure
     * @param string   $escape
     * @param mixed[]|null $headers Leave null to read the headers from the file
     */
    public function __construct(
        $resource,
        protected $delimiter = ',',
        protected $enclosure = '"',
        protected $escape = '\\',
        array $headers = null
    ) {
        if (! \is_resource($resource)) {
            $type = \gettype($resource);
            throw new UnexpectedValueException("Resource argument must be a resource, '{$type}' given");
        }

        $this->handler = $resource;
        $this->headers = $this->parseHeaders($headers);
        $this->headerCount = \count($this->headers);
    }

    /**
     * Closes the resource when the object is destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    public function getEscape(): string
    {
        return $this->escape;
    }

    /**
     * @return resource
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Count the number of CSV lines (with correct enclosure detection), ignoring blank lines.
     *
     * Warning! This method will rewind the file to the beginning before and after counting the lines!
     * Do not use in the middle of a process.
     * This can be very slow.
     */
    public function getLineCount(): int
    {
        if ($this->lineCount === null) {
            $this->rewind();
            $line = 0;
            while (! $this->isEndOfFile()) {
                if ($this->readRaw()) {
                    ++$line;
                }
            }
            $this->rewind();

            $this->lineCount = $line;
        }

        return $this->lineCount;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeaderCount(): int
    {
        return $this->headerCount;
    }

    /**
     * Write headers to the file
     */
    public function writeHeaders(): void
    {
        $this->writeRaw($this->headers);
    }

    public function getLineNumber(): int
    {
        if ($this->seekCalled) {
            throw new LogicException('Cannot get current line number after calling "seek": the line number is lost');
        }

        return $this->lineNumber;
    }

    public function isEndOfFile(): bool
    {
        $this->assertOpened();

        return feof($this->handler);
    }

    /**
     * Warning, this function will return exactly the same value as the fgetcsv() function.
     *
     * @param null|int $length
     */
    public function readRaw($length = null): array|false
    {
        $this->assertOpened();
        ++$this->lineNumber;

        return fgetcsv($this->handler, $length, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * @param int|null $length
     *
     * @return array
     */
    public function readLine($length = null): ?array
    {
        if ($this->seekCalled) {
            $filePosition = "at position {$this->tell()}";
        } else {
            $filePosition = "on line {$this->getLineNumber()}";
        }
        $values = $this->readRaw($length);

        if ($values === false) {
            if ($this->isEndOfFile()) {
                return null;
            }
            $message = "Unable to parse data {$filePosition} for {$this->getResourceName()}";
            throw new UnexpectedValueException($message);
        }

        $count = \count($values);
        if ($count !== $this->headerCount) {
            $message = "Number of columns not matching {$filePosition} for {$this->getResourceName()}: ";
            $message .= "{$count} columns for {$this->headerCount} headers";
            throw new UnexpectedValueException($message);
        }

        $combined = array_combine($this->headers, $values);
        if ($combined === false) {
            throw new RuntimeException('Cannot combine headers with values');
        }

        return $combined;
    }

    /**
     * Warning, this function will return exactly the same value as the fgetcsv() function.
     */
    public function writeRaw(array $fields): int
    {
        $this->assertOpened();
        ++$this->lineNumber;

        return fputcsv($this->handler, $fields, $this->delimiter, $this->enclosure, $this->escape);
    }

    public function writeLine(array $fields): int
    {
        $count = \count($fields);
        if ($count !== $this->headerCount) {
            $message = "Trying to write an invalid number of columns for {$this->getResourceName()}: ";
            $message .= "{$count} columns for {$this->headerCount} headers";
            throw new UnexpectedValueException($message);
        }

        $parsedFields = [];
        foreach ($this->headers as $column) {
            if (! array_key_exists($column, $fields)) {
                $message = "Missing column {$column} in given fields for {$this->getResourceName()}";
                throw new UnexpectedValueException($message);
            }
            $parsedFields[$column] = $fields[$column];
        }

        $length = $this->writeRaw($parsedFields);
        if ($length === false) {
            throw new RuntimeException("Unable to write data to {$this->getResourceName()}");
        }

        return $length;
    }

    /**
     * This methods rewinds the file to the first line of data, skipping the headers.
     */
    public function rewind(): void
    {
        $this->assertOpened();
        if (! rewind($this->handler)) {
            throw new RuntimeException("Unable to rewind '{$this->getResourceName()}'");
        }
        $this->lineNumber = 1;
        if (! $this->manualHeaders) {
            $this->readRaw(); // skip headers if not manual headers
        }
    }

    public function tell(): int
    {
        $this->assertOpened();

        return ftell($this->handler);
    }

    /**
     * @param int $offset
     */
    public function seek($offset): int
    {
        $this->assertOpened();
        $this->seekCalled = true;

        return fseek($this->handler, $offset);
    }

    public function close(): bool
    {
        if ($this->closed) {
            return true;
        }

        $this->closed = fclose($this->handler);

        return $this->closed;
    }

    public function isManualHeaders(): bool
    {
        return $this->manualHeaders;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function getFilePath(): string
    {
        return '';
    }

    protected function assertOpened(): void
    {
        if ($this->closed) {
            throw new RuntimeException("{$this->getResourceName()} was closed earlier");
        }
    }

    protected function parseHeaders(array $headers = null): array
    {
        // If headers are not passed in the constructor but file is readable, try to read headers from file
        if ($headers === null) {
            $autoHeaders = $this->readRaw();
            if ($autoHeaders === false || \count($autoHeaders) === 0) {
                throw new UnexpectedValueException("Unable to read headers for {$this->getResourceName()}");
            }
            // Remove BOM if any
            $bom = pack('H*', 'EFBBBF');
            $autoHeaders[0] = preg_replace("/^{$bom}/", '', (string) $autoHeaders[0]);

            return $autoHeaders;
        }

        $this->manualHeaders = true;
        if ($headers === null || ! \is_array($headers)) {
            throw new UnexpectedValueException(
                "Invalid headers for {$this->getResourceName()}, you need to pass the headers manually"
            );
        }
        if (\count($headers) === 0) {
            throw new UnexpectedValueException(
                "Empty headers for {$this->getResourceName()}, you need to pass the headers manually"
            );
        }

        return $headers;
    }

    protected function getResourceName(): string
    {
        return "CSV resource '{$this->handler}'";
    }
}
