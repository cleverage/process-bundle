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
 * Write data to an XML file
 * 
 * Requires `php-xml`.
 *
 * ##### Task reference
 * 
 *  * **Service**: `CleverAge\ProcessBundle\Task\File\Xml\XmlWriterTask`
 *  * **Input**: `\DOMDocument`
 *  * **Output**: `string`, the resulting file path.
 *
 * ##### Options
 *
 * * `file_path` (`string`, _required_): Path of the file to write into (relative to symfony root or absolute)
 * * `mode` (`string`, _defaults to_ `rb`): File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php))
 *
 * @example "Resources/examples/task/file/xml/xml_writer_task.yaml"
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class XmlWriterTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('file_path');
        $resolver->setAllowedTypes('file_path', 'string');

        $resolver->setDefault('mode', 'wb');
        $resolver->setAllowedTypes('mode', 'string');
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        if (!$input instanceof \DOMDocument) {
            throw new \UnexpectedValueException('Input must be a \DOMDocument');
        }

        $file = new XmlFile($this->getOption($state, 'file_path'), $this->getOption($state, 'mode'));
        $file->write($input);
        $state->setOutput($this->getOption($state, 'file_path'));
    }
}
