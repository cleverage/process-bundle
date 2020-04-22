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
 * Read and write files through a simple API.
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class File extends FileResource
{
    use FileHelperTrait;

    /** @var string */
    protected $filePath;

    /**
     * @param string $filePath Also accept a resource
     * @param string $mode     Same parameter as the mode in the fopen function (r, w, a, etc.)
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    public function __construct(
        $filePath,
        $mode = 'rb'
    ) {
        $this->filePath = $filePath;
        $resource = $this->openResource($filePath, $mode);

        parent::__construct($resource);
    }

    /**
     * Will return a resource if the file was created using a resource
     *
     * @return string|resource
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    protected function getResourceName(): string
    {
        return "file '{$this->filePath}'";
    }
}
