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

namespace CleverAge\ProcessBundle\Task\Reporting;

use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;

/**
 * Count the number of times the task was executed.
 */
class StatCounterTask implements FinalizableTaskInterface
{
    protected int $counter = 0;

    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    public function finalize(ProcessState $state): void
    {
        $this->logger->info("Processed item count: {$this->counter}");
    }

    public function execute(ProcessState $state): void
    {
        ++$this->counter;
    }
}
