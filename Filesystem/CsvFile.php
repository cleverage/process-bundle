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
class CsvFile extends CsvResource
{
    /** @var string */
    protected $filePath;

    /**
     * @param string $filePath Also accept a resource
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param array  $headers  Leave null to read the headers from the file
     * @param string $mode     Same parameter as the mode in the fopen function (r, w, a, etc.)
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

        $resource = fopen($filePath, $mode);
        if (false === $resource) {
            throw new \UnexpectedValueException("Unable to open file: '{$filePath}' in {$mode} mode");
        }
        // Cannot read headers if the file was just created
        if (null === $headers && !in_array($mode, ['r', 'r+', 'w+', 'a+', 'x+', 'c+'], true)) {
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
