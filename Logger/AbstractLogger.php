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

namespace CleverAge\ProcessBundle\Logger;

use Psr\Log\AbstractLogger as BaseAbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Base logic for logger tasks, see inherited services for more information
 *
 * Used for simplified autowiring
 */
abstract class AbstractLogger extends BaseAbstractLogger
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
