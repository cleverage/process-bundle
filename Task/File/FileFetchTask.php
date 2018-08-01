<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FileFetchTask
 *
 * Copy (or move) file from one filesystem to another, using Flysystem
 * Either get files using a file regexp, or take files from input
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class FileFetchTask extends AbstractConfigurableTask implements IterableTaskInterface
{

    /** @var  MountManager */
    protected $mountManager;

    /** @var FilesystemInterface */
    protected $sourceFS;

    /** @var FilesystemInterface */
    protected $destinationFS;

    /** @var array */
    protected $matchingFiles = [];

    /**
     * @param MountManager $mountManager
     */
    public function __construct(MountManager $mountManager)
    {
        $this->mountManager = $mountManager;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \League\Flysystem\FilesystemNotFoundException
     */
    public function initialize(ProcessState $state)
    {
        // Configure options
        parent::initialize($state);

        $this->sourceFS = $this->mountManager->getFilesystem($this->getOption($state, 'source_filesystem'));
        $this->destinationFS = $this->mountManager->getFilesystem($this->getOption($state, 'destination_filesystem'));
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     * @throws \League\Flysystem\FilesystemNotFoundException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function execute(ProcessState $state)
    {
        $this->findMatchingFiles($state);

        $file = current($this->matchingFiles);
        if (!$file) {
            $state->setSkipped(true);

            return;
        }

        $this->doFileCopy($state, $file, $this->getOption($state, 'remove_source'));
        $state->setOutput($file);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     *
     * @return bool|mixed
     */
    public function next(ProcessState $state)
    {
        $this->findMatchingFiles($state);

        return next($this->matchingFiles);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function findMatchingFiles(ProcessState $state)
    {
        $filePattern = $this->getOption($state, 'file_pattern');
        if ($filePattern) {
            foreach ($this->sourceFS->listContents('/') as $file) {
                if ('file' === $file['type']
                    && preg_match($filePattern, $file['path'])
                    && !\in_array($file['path'], $this->matchingFiles, true)) {
                    $this->matchingFiles[] = $file['path'];
                }
            }
        } else {
            $input = $state->getInput();
            if (!$input) {
                throw new \UnexpectedValueException('No pattern neither input provided for the Task');
            }
            if (\is_array($input)) {
                foreach ($input as $file) {
                    if (!\in_array($file, $this->matchingFiles, true)) {
                        $this->matchingFiles[] = $file;
                    }
                }
            } elseif (!\in_array($input, $this->matchingFiles, true)) {
                $this->matchingFiles[] = $input;
            }
        }
    }

    /**
     * @param ProcessState $state
     * @param string       $filename
     * @param bool         $removeSource
     *
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \League\Flysystem\FilesystemNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    protected function doFileCopy(ProcessState $state, $filename, $removeSource)
    {
        $prefixFrom = $this->getOption($state, 'source_filesystem');
        $prefixTo = $this->getOption($state, 'destination_filesystem');

        $buffer = $this->mountManager->getFilesystem($prefixFrom)->readStream($filename);

        if (false === $buffer) {
            return false;
        }

        $result = $this->mountManager->getFilesystem($prefixTo)->putStream($filename, $buffer);

        if (\is_resource($buffer)) {
            fclose($buffer);
        }

        if ($removeSource) {
            $this->mountManager->delete(sprintf('%s://%s', $prefixFrom, $filename));
        }

        return $result ? $filename : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['source_filesystem', 'destination_filesystem']);
        $resolver->setAllowedTypes('source_filesystem', 'string');
        $resolver->setAllowedTypes('destination_filesystem', 'string');

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['string', 'null']);

        $resolver->setDefault('remove_source', false);
        $resolver->setAllowedTypes('remove_source', 'boolean');
    }
}
