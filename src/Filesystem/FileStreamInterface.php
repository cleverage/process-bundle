<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Define a common interface for all file reading systems.
 */
interface FileStreamInterface
{
    public function getLineCount(): int;

    /**
     * Warning! This returns the line number of the pointer inside the file so you need to call it BEFORE reading a line.
     */
    public function getLineNumber(): int;

    public function isEndOfFile(): bool;

    public function readLine(int $length = null): ?array;

    /**
     * This methods rewinds the file to the first line of data, skipping the headers.
     */
    public function rewind(): void;
}
