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
 * Define a common interface for all file with headers
 */
interface StructuredFileInterface extends FileStreamInterface
{
    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return int
     */
    public function getHeaderCount(): int;
}
