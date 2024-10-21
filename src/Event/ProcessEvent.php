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

namespace CleverAge\ProcessBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event object for process start/stop/fail.
 */
class ProcessEvent extends Event
{
    final public const EVENT_PROCESS_STARTED = 'cleverage_process.start';

    final public const EVENT_PROCESS_ENDED = 'cleverage_process.end';

    final public const EVENT_PROCESS_FAILED = 'cleverage_process.fail';

    public function __construct(
        protected string $processCode,
        protected mixed $processInput = null,
        protected array $processContext = [],
        protected mixed $processOutput = null,
        protected ?\Throwable $processError = null,
    ) {
    }

    public function getProcessCode(): string
    {
        return $this->processCode;
    }

    public function getProcessInput(): mixed
    {
        return $this->processInput;
    }

    public function getProcessOutput(): mixed
    {
        return $this->processOutput;
    }

    public function getProcessContext(): array
    {
        return $this->processContext;
    }

    public function getProcessError(): ?\Throwable
    {
        return $this->processError;
    }
}
