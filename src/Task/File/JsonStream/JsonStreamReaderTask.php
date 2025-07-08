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

namespace CleverAge\ProcessBundle\Task\File\JsonStream;

use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonStreamReaderTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected ?JsonStreamFile $file = null;

    public function execute(ProcessState $state): void
    {
        if (!$this->file instanceof JsonStreamFile) {
            $options = $this->getOptions($state);
            $this->file = new JsonStreamFile(
                $this->getFilePath($state),
                'rb',
                $options['spl_file_object_flags'],
                $options['json_flags'],
            );
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

        return !$eof;
    }

    protected function getFilePath(ProcessState $state): string
    {
        return $state->getInput();
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'spl_file_object_flags' => null,
            'json_flags' => null,
        ]);
        $resolver->setAllowedTypes('spl_file_object_flags', ['array', 'null']);
        $resolver->setAllowedTypes('json_flags', ['array', 'null']);
    }
}
