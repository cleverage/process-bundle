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

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dump memory info to file using meminfo extension if available: https://github.com/BitOne/php-meminfo.
 */
class MemInfoDumpTask extends AbstractConfigurableTask
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    public function execute(ProcessState $state): void
    {
        if (\function_exists('meminfo_dump')) {
            gc_collect_cycles();
            $handler = fopen($this->getOption($state, 'file_path'), 'w');
            meminfo_dump($handler);
            fclose($handler);
        } else {
            $this->logger->critical('meminfo PHP extension is not loaded');
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
    }
}
