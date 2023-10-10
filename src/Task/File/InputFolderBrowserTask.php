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

use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Browse a folder with the path as an input and iterate each file for output.
 */
class InputFolderBrowserTask extends FolderBrowserTask implements FlushableTaskInterface
{
    protected ?string $folderPath = null;

    public function flush(ProcessState $state): void
    {
        $this->folderPath = null;
        $state->setSkipped(true);
    }

    public function initialize(ProcessState $state): void
    {
        parent::getOptions($state);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->remove(['folder_path']);

        $resolver->setDefaults([
            'base_folder_path' => '',
        ]);
        $resolver->setAllowedTypes('base_folder_path', ['string']);
    }

    protected function getOptions(ProcessState $state): array
    {
        $options = parent::getOptions($state);
        if ($state->getInput()) {
            $folderPath = $options['base_folder_path'].$state->getInput();
            if ($this->folderPath && $folderPath !== $this->folderPath) {
                throw new \LogicException(
                    "Folder path '{$folderPath}' already initialized with a different value {$this->folderPath}"
                );
            }
            $this->folderPath = $folderPath;
        }

        if (!is_dir($this->folderPath)) {
            throw new InvalidConfigurationException(
                "Folder path does not exists or is not a folder: '{$this->folderPath}'"
            );
        }
        if (!is_readable($this->folderPath)) {
            throw new InvalidConfigurationException("Folder path is not readable: '{$this->folderPath}'");
        }
        $options['folder_path'] = $this->folderPath;

        return $options;
    }
}
