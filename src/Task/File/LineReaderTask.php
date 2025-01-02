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

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads a file line by line and outputs each line.
 */
class LineReaderTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected ?\SplFileObject $file = null;

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $filename = $options['filename'];

        if ($this->file instanceof \SplFileObject
            && $this->file->getPathname() !== $filename) {
            $this->file = null;
        }

        if (!$this->file instanceof \SplFileObject) {
            if (!file_exists($filename)) {
                throw new \UnexpectedValueException("File does not exist: '{$filename}'");
            }

            if (!is_readable($filename)) {
                throw new \UnexpectedValueException("File is not readable: '{$filename}'");
            }

            $this->file = new \SplFileObject($filename);
            $this->file->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
            $this->file->rewind();
        }

        $state->setOutput($this->file->current());
        $this->file->next();
    }

    public function next(ProcessState $state): bool
    {
        if (!$this->file instanceof \SplFileObject) {
            throw new \LogicException('No File initialized');
        }

        return !$this->file->eof();
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['filename']);
        $resolver->setAllowedTypes('filename', ['string']);
    }
}
