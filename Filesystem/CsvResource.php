<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Read and write CSV resources through a simple API.
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvResource implements FileStreamInterface
{
    /** @var string */
    protected $delimiter;

    /** @var string */
    protected $enclosure;

    /** @var string */
    protected $escape;

    /** @var resource */
    protected $handler;

    /** @var int */
    protected $lineCount;

    /** @var array */
    protected $headers;

    /** @var bool */
    protected $manualHeaders = false;

    /** @var int */
    protected $headerCount;

    /** @var int */
    protected $currentLine = 0;

    /** @var bool */
    protected $closed;

    /** @var bool */
    protected $seekCalled = false;

    /**
     * @param resource $resource
     * @param string   $delimiter CSV delimiter
     * @param string   $enclosure
     * @param string   $escape
     * @param array    $headers   Leave null to read the headers from the file
     *
     * @throws \UnexpectedValueException
     */
    public function __construct(
        $resource,
        $delimiter = ',',
        $enclosure = '"',
        $escape = '\\',
        array $headers = null
    ) {
        if (!\is_resource($resource)) {
            $type = \gettype($resource);
            throw new \UnexpectedValueException("Resource argument must be a resource, '{$type}' given");
        }
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;

        $this->handler = $resource;
        $this->headers = $this->parseHeaders($headers);
        $this->headerCount = \count($this->headers);
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * @return string
     */
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
                $this->readRaw();
            }
            $this->rewind();

            $this->lineCount = $line;
        }

        return $this->lineCount;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return int
     */
    public function getHeaderCount(): int
    {
        return $this->headerCount;
    }

    /**
     * Write headers to the file
     *
     * @throws \RuntimeException
     */
    public function writeHeaders(): void
    {
        $this->writeRaw($this->headers);
    }

    /**
     * @throws \LogicException
     *
     * @return int
     */
    public function getCurrentLine(): int
    {
        if ($this->seekCalled) {
            throw new \LogicException('Cannot get current line number after calling "seek": the line number is lost');
        }

        return $this->currentLine;
    }

    /**
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function isEndOfFile(): bool
    {
        $this->assertOpened();

        return feof($this->handler);
    }

    /**
     * Warning, this function will return exactly the same value as the fgetcsv() function.
     *
     * @param null|int $length
     *
     * @throws \RuntimeException
     *
     * @return array|false
     */
    public function readRaw($length = null)
    {
        $this->assertOpened();
        ++$this->currentLine;

        return fgetcsv($this->handler, $length, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * @param int|null $length
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return array
     */
    public function readLine($length = null): ?array
    {
        $values = $this->readRaw($length);

        if (false === $values) {
            if ($this->isEndOfFile()) {
                return null;
            }
            $message = "Unable to parse data on line {$this->currentLine} for {$this->getResourceName()}";
            throw new \UnexpectedValueException($message);
        }

        $count = \count($values);
        if ($count !== $this->headerCount) {
            $message = "Number of columns not matching on line {$this->currentLine} for {$this->getResourceName()}: ";
            $message .= "{$count} columns for {$this->headerCount} headers";
            throw new \UnexpectedValueException($message);
        }

        $combined = array_combine($this->headers, $values);
        if (false === $combined) {
            throw new \RuntimeException('Cannot combine headers with values');
        }

        return $combined;
    }

    /**
     * Warning, this function will return exactly the same value as the fgetcsv() function.
     *
     * @param array $fields
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function writeRaw(array $fields): int
    {
        $this->assertOpened();
        ++$this->currentLine;

        return fputcsv($this->handler, $fields, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * @param array $fields
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function writeLine(array $fields): int
    {
        $count = \count($fields);
        if ($count !== $this->headerCount) {
            $message = "Trying to write an invalid number of columns for {$this->getResourceName()}: ";
            $message .= "{$count} columns for {$this->headerCount} headers";
            throw new \UnexpectedValueException($message);
        }

        $parsedFields = [];
        foreach ($this->headers as $column) {
            if (!array_key_exists($column, $fields)) {
                $message = "Missing column {$column} in given fields for {$this->getResourceName()}";
                throw new \UnexpectedValueException($message);
            }
            $parsedFields[$column] = $fields[$column];
        }

        $length = $this->writeRaw($parsedFields);
        if (false === $length) {
            throw new \RuntimeException("Unable to write data to {$this->getResourceName()}");
        }

        return $length;
    }

    /**
     * This methods rewinds the file to the first line of data, skipping the headers.
     *
     * @throws \RuntimeException
     */
    public function rewind(): void
    {
        $this->assertOpened();
        if (!rewind($this->handler)) {
            throw new \RuntimeException("Unable to rewind '{$this->getResourceName()}'");
        }
        $this->currentLine = 0;
        if (!$this->manualHeaders) {
            $this->readRaw(); // skip headers
        }
    }

    /**
     * @throws \RuntimeException
     *
     * @return int
     */
    public function tell(): int
    {
        $this->assertOpened();

        return ftell($this->handler);
    }

    /**
     * @param int $offset
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function seek($offset): int
    {
        $this->assertOpened();
        $this->seekCalled = true;

        return fseek($this->handler, $offset);
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        if ($this->closed) {
            return true;
        }

        $this->closed = fclose($this->handler);

        return $this->closed;
    }

    /**
     * @return bool
     */
    public function isManualHeaders(): bool
    {
        return $this->manualHeaders;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Closes the resource when the object is destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @throws \RuntimeException
     */
    protected function assertOpened(): void
    {
        if ($this->closed) {
            throw new \RuntimeException("{$this->getResourceName()} was closed earlier");
        }
    }

    /**
     * @param array $headers
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    protected function parseHeaders(array $headers = null): array
    {
        // If headers are not passed in the constructor but file is readable, try to read headers from file
        if (null === $headers) {
            $headers = fgetcsv($this->handler, 0, $this->delimiter, $this->enclosure, $this->escape);
            if (false === $headers || 0 === \count($headers)) {
                throw new \UnexpectedValueException("Unable to read headers for {$this->getResourceName()}");
            }
            // Remove BOM if any
            $bom = pack('H*', 'EFBBBF');
            $headers[0] = preg_replace("/^{$bom}/", '', $headers[0]);

            return $headers;
        }

        $this->manualHeaders = true;
        if (null === $headers || !\is_array($headers)) {
            throw new \UnexpectedValueException(
                "Invalid headers for {$this->getResourceName()}, you need to pass the headers manually"
            );
        }
        if (0 === \count($headers)) {
            throw new \UnexpectedValueException(
                "Empty headers for {$this->getResourceName()}, you need to pass the headers manually"
            );
        }

        return $headers;
    }

    /**
     * @return string
     */
    protected function getResourceName(): string
    {
        return "CSV resource '{$this->handler}'";
    }
}
