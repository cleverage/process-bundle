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

namespace CleverAge\ProcessBundle\Task\File\JsonStream;

use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

class JsonStreamReaderTask implements IterableTaskInterface
{
    protected ?JsonStreamFile $file = null;


    public function execute(ProcessState $state): void
    {
        if ($this->file === null) {
            $this->file = new JsonStreamFile($this->getFilePath($state), 'rb');
        }

        $line = $this->file->readLine();
        if (isset($line)) {
            $state->setOutput($line);
        } else {
            $state->setSkipped(true);
        }
    }

    public function next(ProcessState $state): bool
    {
        $eof = $this->file->isEndOfFile();
        if ($eof) {
            $this->file = null;
        }

        return ! $eof;
    }

    protected function getFilePath(ProcessState $state): string
    {
        return $state->getInput();
    }
}
