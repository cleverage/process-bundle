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

namespace CleverAge\ProcessBundle\Task\File\Yaml;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads a YAML file and iterate over its root elements.
 */
class YamlReaderTask extends AbstractIterableOutputTask
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setNormalizer(
            'file_path',
            static function (Options $options, $value) {
                if (!file_exists($value)) {
                    throw new \UnexpectedValueException("File not found: {$value}");
                }

                return $value;
            }
        );
    }

    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $filePath = $this->getOption($state, 'file_path');
        $content = Yaml::parseFile($filePath);
        if (!\is_array($content)) {
            throw new \InvalidArgumentException("File content is not an array: {$filePath}");
        }

        return new \ArrayIterator($content);
    }
}
