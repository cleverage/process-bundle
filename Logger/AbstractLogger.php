<?php declare(strict_types=1);

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
