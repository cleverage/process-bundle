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

/**
 * Define a common interface for seekable files.
 */
interface SeekableFileInterface extends FileStreamInterface
{
    /**
     * Returns the current position of the cursor inside the file.
     */
    public function tell(): int;

    /**
     * Go to a specific position inside the file.
     */
    public function seek(int $offset): int;
}
