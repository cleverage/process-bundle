<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task\Reporting;

use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;

/**
 * Count the number of times the task was executed
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class StatCounterTask implements FinalizableTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var int */
    protected $counter = 0;

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
    public function finalize(ProcessState $state)
    {
        $logContext = $state->getLogContext();
        $this->logger->info("Processed item count: {$this->counter}", $logContext);
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $this->counter++;
    }
}
