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
 * Define a common interface for all file reading systems
 */
interface FileStreamInterface
{
    /**
     * @return int
     */
    public function getLineCount(): int;

    /**
     * Warning! This returns the line number of the pointer inside the file so you need to call it BEFORE reading a line
     *
     * @return int
     */
    public function getLineNumber(): int;

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
     * This methods rewinds the file to the first line of data, skipping the headers.
     */
    public function rewind(): void;
}
