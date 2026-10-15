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

namespace CleverAge\ProcessBundle\Task\File\Xml;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Read an XML file element by element with \XMLReader, so that the whole file is never loaded in memory.
 *
 * @final since 5.4
 */
class XmlStreamReaderTask extends AbstractConfigurableTask implements IterableTaskInterface, FinalizableTaskInterface
{
    protected ?\XMLReader $reader = null;

    protected ?string $filePath = null;

    public function execute(ProcessState $state): void
    {
        $nodeName = (string) $this->getOption($state, 'node_name');
        if ($this->reader instanceof \XMLReader) {
            // Skip the subtree of the current element: nested elements with the same name are not iterated
            $found = $this->moveToElement($nodeName, true);
        } else {
            $this->open($this->getFilePath($state));
            $found = $this->moveToElement($nodeName, false);
        }

        if (!$found) {
            // Release the file to allow the following iteration to read it again
            $this->close();
            $state->setSkipped(true);

            return;
        }

        $state->setOutput($this->expand());
    }

    /**
     * The reader is moved in execute(), so that XML errors are handled by the error strategy of the task.
     */
    public function next(ProcessState $state): bool
    {
        return $this->reader instanceof \XMLReader;
    }

    public function finalize(ProcessState $state): void
    {
        $this->close();
    }

    protected function getFilePath(ProcessState $state): string
    {
        $filePath = $this->getOption($state, 'file_path') ?? $state->getInput();
        if (!\is_string($filePath) || '' === $filePath) {
            throw new \UnexpectedValueException('No file path given in the "file_path" option or as input');
        }

        return $filePath;
    }

    protected function open(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \UnexpectedValueException(\sprintf('XML file "%s" does not exist or is not readable', $filePath));
        }

        $reader = new \XMLReader();
        if (!@$reader->open($filePath)) {
            throw new \RuntimeException(\sprintf('Could not open XML file "%s"', $filePath));
        }
        $this->reader = $reader;
        $this->filePath = $filePath;
    }

    /**
     * Move the reader to the next element named $nodeName, return false at the end of the file.
     */
    protected function moveToElement(string $nodeName, bool $skipCurrent): bool
    {
        $reader = $this->getReader();

        $previousUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $found = $skipCurrent ? $reader->next() : $reader->read();
            while ($found && (\XMLReader::ELEMENT !== $reader->nodeType || $reader->name !== $nodeName)) {
                $found = $reader->read();
            }
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        $this->throwOnErrors($errors);

        return $found;
    }

    protected function expand(): \DOMElement
    {
        $reader = $this->getReader();
        $document = new \DOMDocument();

        $previousUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $node = $reader->expand($document);
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        $this->throwOnErrors($errors);
        if (!$node instanceof \DOMElement) {
            throw new \UnexpectedValueException(\sprintf('Could not expand element "%s" of XML file "%s"', $reader->name, $this->filePath));
        }

        // Each element gets its own document, so that it can be evaluated with xpath like the output of XmlReaderTask
        $document->appendChild($node);

        return $node;
    }

    /**
     * Warnings are tolerated, errors (e.g. mismatched tags) and fatal errors are not.
     *
     * @param array<\LibXMLError> $errors
     */
    protected function throwOnErrors(array $errors): void
    {
        $errors = array_filter($errors, static fn (\LibXMLError $error): bool => \LIBXML_ERR_WARNING !== $error->level);
        if ([] === $errors) {
            return;
        }

        $messages = array_map(
            static fn (\LibXMLError $error): string => \sprintf('%s (line %d, column %d)', trim($error->message), $error->line, $error->column),
            $errors,
        );
        $filePath = $this->filePath;
        $this->close();

        throw new \UnexpectedValueException(\sprintf('Invalid XML in file "%s": %s', $filePath, implode('; ', $messages)));
    }

    protected function getReader(): \XMLReader
    {
        if (!$this->reader instanceof \XMLReader) {
            throw new \LogicException('No XML file opened');
        }

        return $this->reader;
    }

    protected function close(): void
    {
        $this->reader?->close();
        $this->reader = null;
        $this->filePath = null;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('file_path', null);
        $resolver->setAllowedTypes('file_path', ['string', 'null']);

        $resolver->setRequired('node_name');
        $resolver->setAllowedTypes('node_name', 'string');
    }
}
