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
 * Define a common interface for all file with headers.
 */
interface WritableStructuredFileInterface extends StructuredFileInterface, WritableFileInterface
{
    /**
     * Write headers to the file.
     */
    public function writeHeaders(): void;
}
