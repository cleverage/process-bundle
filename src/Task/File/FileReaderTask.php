<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Read the whole file and output its content.
 */
class FileReaderTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $filename = $options['filename'];

        if (!file_exists($filename)) {
            throw new \UnexpectedValueException("File does not exists: '{$filename}'");
        }

        if (!is_readable($filename)) {
            throw new \UnexpectedValueException("File is not readable: '{$filename}'");
        }

        $state->setOutput(file_get_contents($filename));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['filename']);
        $resolver->setAllowedTypes('filename', ['string']);
    }
}
