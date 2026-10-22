<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Filesystem;

use CleverAge\ProcessBundle\Exception\RuntimeException;

/**
 * Creates the temporary files of the bundle in the system temporary directory: random name, exclusive creation (an
 * existing path, symbolic links included, is never reused) and readable by their owner only.
 *
 * @internal
 */
final class TemporaryFile
{
    /**
     * Creates an empty file and returns its path.
     */
    public static function create(string $prefix, string $suffix = ''): string
    {
        $path = sys_get_temp_dir().\DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(16)).$suffix;
        $file = @fopen($path, 'x');
        if (false === $file) {
            throw new RuntimeException("Unable to create temporary file {$path}");
        }
        fclose($file);
        chmod($path, 0o600);

        return $path;
    }
}
