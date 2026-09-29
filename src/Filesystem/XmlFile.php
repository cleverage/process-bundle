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

/**
 * Read and write XML files.
 */
class XmlFile
{
    protected \SplFileObject $file;

    public function __construct(string $path, string $mode = 'rb')
    {
        $this->file = new \SplFileObject($path, $mode);
    }

    public function read(): \DOMDocument
    {
        $this->file->rewind();
        // fstat() on the open handle, as getSize() may return a stale size from the stat cache
        $fileSize = $this->file->fstat()['size'];
        if (0 === $fileSize) {
            throw new \UnexpectedValueException(\sprintf('XML file "%s" is empty', $this->file->getPathname()));
        }

        $fileContent = $this->file->fread($fileSize);
        if (false === $fileContent) {
            throw new \RuntimeException(\sprintf('Could not read content from XML file "%s"', $this->file->getPathname()));
        }

        $dom = new \DOMDocument();
        $previousUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $loaded = $dom->loadXML($fileContent);
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        // Warnings are tolerated, errors (e.g. undefined namespace prefix) and fatal errors are not
        $errors = array_filter($errors, static fn (\LibXMLError $error): bool => \LIBXML_ERR_WARNING !== $error->level);
        if (!$loaded || [] !== $errors) {
            $messages = array_map(
                static fn (\LibXMLError $error): string => \sprintf('%s (line %d, column %d)', trim($error->message), $error->line, $error->column),
                $errors,
            );

            throw new \UnexpectedValueException(\sprintf('Invalid XML in file "%s": %s', $this->file->getPathname(), [] !== $messages ? implode('; ', $messages) : 'unknown error'));
        }

        return $dom;
    }

    public function write(\DOMDocument $dom): void
    {
        $content = $dom->saveXML();
        if (false === $content) {
            throw new \RuntimeException('Could not generate the XML content');
        }
        $result = $this->file->fwrite($content);

        if (false === $result) {
            throw new \RuntimeException('Could not write content to file');
        }
    }
}
