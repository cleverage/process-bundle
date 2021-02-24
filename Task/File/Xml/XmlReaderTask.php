<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File\Xml;

use CleverAge\ProcessBundle\Filesystem\XmlFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Read an XML file
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class XmlReaderTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * XmlReaderTask constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('file_path');
        $resolver->setAllowedTypes('file_path', 'string');

        $resolver->setDefault('mode', 'rb');
        $resolver->setAllowedTypes('mode', 'string');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ProcessState $state)
    {
        if ($state->getInput() !== null) {
            $this->logger->warning('Input has been ignored for XMLReaderTask');
        }

        $file = new XmlFile($this->getOption($state, 'file_path'), $this->getOption($state, 'mode'));
        $state->setOutput($file->read());
    }
}
