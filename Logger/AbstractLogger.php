<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Logger;

use Psr\Log\AbstractLogger as BaseAbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Base logic for logger tasks, see inherited services for more information
 *
 * Used for simplified autowiring
 *
 * @internal
 */
abstract class AbstractLogger extends BaseAbstractLogger
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * TaskLoggger constructor.
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
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
