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

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Ouputs the stopwatch  the content of the input.
 */
class StopwatchTask implements TaskInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        private readonly Stopwatch $stopwatch
    ) {
    }

    public function execute(ProcessState $state): void
    {
        foreach ($this->stopwatch->getSectionEvents('__root__') as $event) {
            $this->logger->info($event);
        }
    }
}
