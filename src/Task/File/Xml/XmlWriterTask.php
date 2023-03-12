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

namespace CleverAge\ProcessBundle\Task\File\Xml;

use CleverAge\ProcessBundle\Filesystem\XmlFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use DOMDocument;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

/**
 * Write an XML file
 */
class XmlWriterTask extends AbstractConfigurableTask
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $input = $state->getInput();
        if (! $input instanceof DOMDocument) {
            throw new UnexpectedValueException('Input must be a \DOMDocument');
        }

        $file = new XmlFile($this->getOption($state, 'file_path'), $this->getOption($state, 'mode'));
        $file->write($input);
        $state->setOutput($this->getOption($state, 'file_path'));
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('file_path');
        $resolver->setAllowedTypes('file_path', 'string');

        $resolver->setDefault('mode', 'wb');
        $resolver->setAllowedTypes('mode', 'string');
    }
}
