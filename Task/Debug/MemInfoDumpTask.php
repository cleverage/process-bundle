<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dump memory info to file using meminfo extension if available: https://github.com/BitOne/php-meminfo
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class MemInfoDumpTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        if (function_exists('meminfo_dump')) {
            gc_collect_cycles();
            $handler = fopen($this->getOption($state, 'file_path'), 'wb');
            \meminfo_dump($handler);
            fclose($handler);
        } else {
            $this->logger->critical('meminfo PHP extension is not loaded');
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'file_path',
            ]
        );
        $resolver->setAllowedTypes('file_path', ['string']);
    }
}
