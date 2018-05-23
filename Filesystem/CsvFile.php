<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Read and write CSV files through a simple API.
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CsvFile extends CsvResource
{
    /** @var string */
    protected $filePath;

    /**
     * @param string $filePath  Also accept a resource
     * @param string $delimiter CSV delimiter
     * @param string $enclosure
     * @param string $escape
     * @param array  $headers   Leave null to read the headers from the file
     * @param string $mode      Same parameter as the mode in the fopen function (r, w, a, etc.)
     *
     * @throws \RuntimeException
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

        $dirname = \dirname($this->filePath);
        if (!@mkdir($dirname, 0755, true) && !is_dir($dirname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }

        $resource = fopen($filePath, $mode);
        if (false === $resource) {
            throw new \UnexpectedValueException("Unable to open file: '{$filePath}' in {$mode} mode");
        }
        // Cannot read headers if the file was just created
        if (null === $headers && !\in_array($mode, ['r', 'r+', 'w+', 'a+', 'x+', 'c+'], true)) {
            throw new \UnexpectedValueException(
                "Invalid headers for {$this->getResourceName()}, you need to pass the headers manually"
            );
        }

        parent::__construct($resource, $delimiter, $enclosure, $escape, $headers);
    }

    /**
     * Will return a resource if the file was created using a resource
     *
     * @return string|resource
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    protected function getResourceName()
    {
        return "CSV file '{$this->filePath}'";
    }
}
