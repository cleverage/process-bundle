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
interface WritableFileInterface extends FileStreamInterface
{
    /**
     * @param array $fields
     *
     * @return int
     */
    public function writeLine(array $fields): int;
}
