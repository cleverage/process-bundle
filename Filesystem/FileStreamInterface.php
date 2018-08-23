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
 * Define a common interface for all file reading systems
 */
interface FileStreamInterface
{
    /**
     * @return int
     */
    public function getLineCount(): int;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return int
     */
    public function getHeaderCount(): int;

    /**
     * @return int
     */
    public function getCurrentLine(): int;

    /**
     * @return bool
     */
    public function isEndOfFile(): bool;

    /**
     * @param int|null $length
     *
     * @return array|null
     */
    public function readLine($length = null): ?array;

    /**
     * @param array $fields
     *
     * @return int
     */
    public function writeLine(array $fields): int;

    /**
     * This methods rewinds the file to the first line of data, skipping the headers.
     */
    public function rewind(): void;
}
