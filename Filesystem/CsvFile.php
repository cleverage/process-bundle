<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Read and write CSV files through a simple API.
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvFile
{
    /** @var string */
    protected $filePath;

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
    protected $isClosed;

    /**
     * @param string $filePath
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param array  $headers Leave null to read the headers from the file
     * @param string $mode    Same parameter as the mode in the fopen function (r, w, a, etc.)
     *
     * @throws \UnexpectedValueException
     */
    public function __construct(
        $filePath,
        $delimiter = ',',
        $enclosure = '"',
        $escape = '\\',
        array $headers = null,
        $mode = 'r'
    ) {
        $this->filePath = $filePath;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;

        $this->handler = fopen($filePath, $mode);
        if (false === $this->handler) {
            throw new \UnexpectedValueException("Unable to open file: '{$filePath}' in {$mode} mode");
        }
        $this->headers = $this->parseHeaders($mode, $headers);
        $this->headerCount = count($this->headers);
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * @return string
     */
    public function getEscape()
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
     * @throws \RuntimeException
     *
     * @return int
     */
    public function getLineCount()
    {
        if (null === $this->lineCount) {
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
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return int
     */
    public function getHeaderCount()
    {
        return $this->headerCount;
    }

    /**
     * Write headers to the file
     *
     * @throws \RuntimeException
     */
    public function writeHeaders()
    {
        $this->writeRaw($this->headers);
    }

    /**
     * @return int
     */
    public function getCurrentLine()
    {
        return $this->currentLine;
    }

    /**
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function isEndOfFile()
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
     * @return array
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
     * @return array|null
     */
    public function readLine($length = null)
    {
        $values = $this->readRaw($length);

        if (false === $values) {
            if ($this->isEndOfFile()) {
                return null;
            }
            $message = "Unable to parse data on line {$this->currentLine} for file {$this->filePath}";
            throw new \UnexpectedValueException($message);
        }

        $count = count($values);
        if ($count !== $this->headerCount) {
            $message = "Number of columns not matching on line {$this->currentLine} for file {$this->filePath}: ";
            $message .= "{$count} columns for {$this->headerCount} headers";
            throw new \UnexpectedValueException($message);
        }

        return array_combine($this->headers, $values);
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
    public function writeRaw(array $fields)
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
    public function writeLine(array $fields)
    {
        $count = count($fields);
        if ($count !== $this->headerCount) {
            $message = "Trying to write an invalid number of columns for file {$this->filePath}: ";
            $message .= "{$count} columns for {$this->headerCount} headers";
            throw new \UnexpectedValueException($message);
        }

        $parsedFields = [];
        foreach ($this->headers as $column) {
            if (!array_key_exists($column, $fields)) {
                $message = "Missing column {$column} in given fields for file {$this->filePath}";
                throw new \UnexpectedValueException($message);
            }
            $parsedFields[$column] = $fields[$column];
        }

        $length = $this->writeRaw($parsedFields);
        if (false === $length) {
            throw new \RuntimeException("Unable to write CSV data to file '{$this->filePath}'");
        }

        return $length;
    }

    /**
     * This methods rewinds the file to the first line of data, skipping the headers.
     *
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function rewind()
    {
        $this->assertOpened();
        if (!rewind($this->handler)) {
            throw new \RuntimeException('Unable to rewind CSV resource file');
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
    public function tell()
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
    public function seek($offset)
    {
        $this->assertOpened();

        return fseek($this->handler, $offset);
    }

    /**
     * @return bool
     */
    public function close()
    {
        if ($this->isClosed) {
            return true;
        }

        $this->isClosed = fclose($this->handler);

        return $this->isClosed;
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
    protected function assertOpened()
    {
        if ($this->isClosed) {
            throw new \RuntimeException('Resource handler was closed earlier');
        }
    }

    /**
     * @param array  $headers
     * @param string $mode
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    protected function parseHeaders($mode, array $headers = null)
    {
        // If headers are not passed in the constructor but file is readable, try to read headers from file
        if (null === $headers && in_array($mode, ['r', 'r+', 'w+', 'a+', 'x+', 'c+'], true)) {
            $headers = fgetcsv($this->handler, null, $this->delimiter, $this->enclosure, $this->escape);
            if (false === $headers || 0 === count($headers)) {
                throw new \UnexpectedValueException("Unable to read CSV headers for file: {$this->filePath}");
            }
            // Remove BOM if any
            $bom = pack('H*','EFBBBF');
            $headers[0] = preg_replace("/^{$bom}/", '', $headers[0]);

            return $headers;
        }

        $this->manualHeaders = true;
        if (null === $headers || !is_array($headers)) {
            throw new \UnexpectedValueException(
                "Invalid headers for CSV file '{$this->filePath}', you need to pass the headers manually"
            );
        }
        if (0 === count($headers)) {
            throw new \UnexpectedValueException(
                "Empty headers for CSV file '{$this->filePath}', you need to pass the headers manually"
            );
        }

        return $headers;
    }
}
