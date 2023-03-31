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

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\MountManager;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

use function in_array;
use function is_array;
use function is_resource;

/**
 * Class FileFetchTask
 *
 * Copy (or move) file from one filesystem to another, using Flysystem
 * Either get files using a file regexp, or take files from input
 */
class FileFetchTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected Filesystem $sourceFS;

    protected Filesystem $destinationFS;

    protected array $matchingFiles = [];

    public function __construct(
        protected ?MountManager $mountManager = null
    ) {
    }

    public function initialize(ProcessState $state): void
    {
        if (! $this->mountManager) {
            throw new ServiceNotFoundException('MountManager service not found, you need to install FlySystemBundle');
        }
        // Configure options
        parent::initialize($state);

        $this->sourceFS = new Filesystem($this->getOption($state, 'source_filesystem'));
        $this->destinationFS = new Filesystem($this->getOption($state, 'destination_filesystem'));
    }


    public function execute(ProcessState $state): void
    {
        $this->findMatchingFiles($state);

        $file = current($this->matchingFiles);
        if (! $file) {
            $state->setSkipped(true);

            return;
        }

        $this->doFileCopy($state, $file, $this->getOption($state, 'remove_source'));
        $state->setOutput($file);
    }


    public function next(ProcessState $state): mixed
    {
        $this->findMatchingFiles($state);

        return next($this->matchingFiles);
    }


    protected function findMatchingFiles(ProcessState $state): void
    {
        $filePattern = $this->getOption($state, 'file_pattern');
        if ($filePattern) {
            foreach ($this->sourceFS->listContents('/') as $file) {
                if ($file['type'] === 'file'
                    && preg_match($filePattern, (string) $file['path'])
                    && ! in_array($file['path'], $this->matchingFiles, true)) {
                    $this->matchingFiles[] = $file['path'];
                }
            }
        } else {
            $input = $state->getInput();
            if (! $input) {
                throw new UnexpectedValueException('No pattern neither input provided for the Task');
            }
            if (is_array($input)) {
                foreach ($input as $file) {
                    if (! in_array($file, $this->matchingFiles, true)) {
                        $this->matchingFiles[] = $file;
                    }
                }
            } elseif (! in_array($input, $this->matchingFiles, true)) {
                $this->matchingFiles[] = $input;
            }
        }
    }


    protected function doFileCopy(ProcessState $state, string $filename, bool $removeSource): string|bool|null
    {
        $prefixFrom = $this->getOption($state, 'source_filesystem');

        $buffer = $this->sourceFS->readStream($filename);

        try {
            $this->destinationFS->writeStream($filename, $buffer);
            $result = true;
        } catch (FilesystemException) {
            $result = false;
        }

        if (is_resource($buffer)) {
            fclose($buffer);
        }

        if ($removeSource) {
            $this->sourceFS->delete(sprintf('%s://%s', $prefixFrom, $filename));
        }

        return $result ? $filename : null;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['source_filesystem', 'destination_filesystem']);
        $resolver->setAllowedTypes('source_filesystem', 'string');
        $resolver->setAllowedTypes('destination_filesystem', 'string');

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['string', 'null']);

        $resolver->setDefault('remove_source', false);
        $resolver->setAllowedTypes('remove_source', 'boolean');
    }
}
