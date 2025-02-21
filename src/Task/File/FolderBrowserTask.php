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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Browse a folder an iterate each file for output.
 */
class FolderBrowserTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    /**
     * @var \Iterator|SplFileInfo[]|null
     */
    protected \Iterator|array|null $files = null;

    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        if (null === $this->files) {
            $finder = new Finder();
            $finder->files();
            if ($options['name_pattern']) {
                $finder->name($options['name_pattern']);
            }
            $this->files = $finder->in($options['folder_path'])->sortByName()->getIterator();
            $this->files->rewind();
        }

        if (!$this->files->valid()) {
            $this->logger->log($options['empty_log_level'], "No item found in path {$options['folder_path']}");
            $state->setSkipped(true);
            $state->setErrorOutput($options['folder_path']);
            $this->files = null;

            return;
        }
        /** @var SplFileInfo $fileInfo */
        $fileInfo = $this->files->current();
        $filePath = $fileInfo->getPathname();
        $state->addErrorContextValue('current_file_path', $filePath);
        $state->setOutput($filePath);
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration.
     */
    public function next(ProcessState $state): bool
    {
        if (!$this->files) {
            return false;
        }
        $this->files->next();
        $state->removeErrorContext('current_file_path');

        return $this->files->valid();
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['folder_path']);
        $resolver->setAllowedTypes('folder_path', ['string']);
        $resolver->setNormalizer(
            'folder_path',
            static function (Options $options, $value) {
                if (!is_dir($value)) {
                    throw new InvalidConfigurationException("Folder path does not exists or is not a folder: '{$value}'");
                }
                if (!is_readable($value)) {
                    throw new InvalidConfigurationException("Folder path is not readable: '{$value}'");
                }

                return $value;
            }
        );
        $resolver->setDefaults([
            'name_pattern' => null,
            'empty_log_level' => LogLevel::WARNING,
        ]);
        $resolver->setAllowedTypes('name_pattern', ['null', 'string', 'array']);
        $resolver->setAllowedValues(
            'empty_log_level',
            [
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::DEBUG,
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
            ]
        );
    }
}
