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

namespace CleverAge\ProcessBundle\Model;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use DateTime;
use Stringable;

/**
 * Logs information about a process
 */
class ProcessHistory implements Stringable
{
    final public const STATE_STARTED = 'started';

    final public const STATE_SUCCESS = 'success';

    final public const STATE_FAILED = 'failed';

    protected float $id;

    protected string $processCode;

    protected DateTime $startDate;

    /**
     * @var DateTime
     */
    protected $endDate;

    /**
     * @var string
     */
    protected $state = self::STATE_STARTED;

    public function __construct(
        ProcessConfiguration $processConfiguration,
        protected array $context = []
    ) {
        $this->id = microtime(true);
        $this->processCode = $processConfiguration->getCode();
        $this->startDate = new DateTime();
    }

    public function __toString(): string
    {
        $reference = $this->getProcessCode() . '[' . $this->getState() . ']';
        $time = $this->getStartDate()
            ->format(DateTime::ATOM);

        return $reference . ': ' . $time;
    }

    public function getId(): float
    {
        return $this->id;
    }

    public function getProcessCode(): string
    {
        return $this->processCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Set the process as failed
     */
    public function setFailed(): void
    {
        $this->endDate = new DateTime();
        $this->state = self::STATE_FAILED;
    }

    /**
     * Set the process as succeded
     */
    public function setSuccess(): void
    {
        $this->endDate = new DateTime();
        $this->state = self::STATE_SUCCESS;
    }

    /**
     * Is true when the process is running
     */
    public function isStarted(): bool
    {
        return $this->state === self::STATE_STARTED;
    }

    public function isFailed(): bool
    {
        return $this->state === self::STATE_FAILED;
    }

    /**
     * Get process duration in seconds
     *
     * @return int|null
     */
    public function getDuration()
    {
        if ($this->getEndDate()) {
            return $this->getEndDate()
                ->getTimestamp() - $this->getStartDate()
                ->getTimestamp();
        }

        return null;
    }
}
