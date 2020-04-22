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
 * Defines the interface for basic file reading systems (not structured)
 */
interface FileInterface extends FileStreamInterface
{
    /**
     * @param int|null $length
     *
     * @return string|null
     */
    public function readLine($length = null): ?string;
}
