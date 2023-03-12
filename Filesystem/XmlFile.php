<?php declare(strict_types=1);
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
 * Read and write XML files
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class XmlFile
{
    /** @var \SplFileObject */
    protected $file;

    /**
     * XmlFile constructor.
     *
     * @param string $path
     * @param string $mode
     */
    public function __construct(string $path, string $mode = 'rb')
    {
        $this->file = new \SplFileObject($path, $mode);
    }

    public function read(): \DOMDocument
    {
        $dom = new \DOMDocument();
        $this->file->rewind();
        $fileSize = $this->file->getSize();
        $fileContent = $this->file->fread($fileSize);

        $dom->loadXML($fileContent);

        return $dom;
    }

    public function write(\DOMDocument $dom)
    {
        $content = $dom->saveXML();
        $result = $this->file->fwrite($content);

        if ($result === null) {
            throw new \RuntimeException("Could not write content to file");
        }
    }
}
