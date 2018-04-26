<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Browse a folder an iterate each file for output
 */
class FolderBrowserTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    /** @var \Iterator|SplFileInfo[] */
    protected $files;

    /**
     * @param ProcessState $state
     *
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
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
            $state->log("No item found in path {$options['folder_path']}", LogLevel::WARNING);
            $state->setSkipped(true);
            $state->setError($options['folder_path']);

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
     * return false if the task has terminated it's iteration
     *
     * @param ProcessState $state
     *
     * @throws \LogicException
     *
     * @return bool
     */
    public function next(ProcessState $state)
    {
        if (!$this->files) {
            throw new \LogicException('No file iterator defined');
        }
        $this->files->next();
        $state->removeErrorContext('current_file_path');

        return $this->files->valid();
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'folder_path',
            ]
        );
        $resolver->setAllowedTypes('folder_path', ['string']);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'folder_path',
            function (Options $options, $value) {
                if (!is_dir($value)) {
                    throw new InvalidConfigurationException(
                        "Folder path does not exists or is not a folder: '{$value}'"
                    );
                }
                if (!is_readable($value)) {
                    throw new InvalidConfigurationException("Folder path is not readable: '{$value}'");
                }

                return $value;
            }
        );
        $resolver->setDefaults(
            [
                'name_pattern' => null,
            ]
        );
        $resolver->setAllowedTypes('name_pattern', ['NULL', 'string']);
    }
}
