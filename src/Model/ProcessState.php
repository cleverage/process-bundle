<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Model;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;

/**
 * Used to pass information between tasks.
 */
class ProcessState
{
    final public const STATUS = [
        self::STATUS_NEW,
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_RESOLVED,
    ];

    final public const STATUS_NEW = 'new';

    final public const STATUS_PENDING = 'pending';

    final public const STATUS_PROCESSING = 'processing';

    final public const STATUS_RESOLVED = 'resolved';

    protected TaskConfiguration $taskConfiguration;

    protected mixed $input = null;

    protected mixed $output = null;

    protected mixed $errorOutput = null;

    protected bool $hasErrorOutput = false;

    protected bool $stopped = false;

    protected ?\Throwable $exception = null;

    protected array $errorContext = [];

    protected ?int $returnCode = null;

    protected bool $skipped;

    protected ?array $context = null;

    protected ?ContextualOptionResolver $contextualOptionResolver = null;

    protected ?array $contextualizedOptions = null;

    protected ?ProcessState $previousState = null;

    protected string $status = self::STATUS_NEW;

    public function __construct(
        protected ProcessConfiguration $processConfiguration,
        protected ProcessHistory $processHistory
    ) {
    }

    public function setContextualOptionResolver(ContextualOptionResolver $contextualOptionResolver): void
    {
        $this->contextualOptionResolver = $contextualOptionResolver;
    }

    /**
     * Clone the current object and keep a back reference.
     */
    public function duplicate(): self
    {
        $newState = clone $this;
        $newState->setPreviousState($this);

        return $newState;
    }

    /**
     * Reset the state object
     * To be used before execution.
     */
    public function reset(bool $cleanInput): void
    {
        $this->setOutput(null);
        $this->setSkipped(false);
        $this->setException();
        $this->errorOutput = null;
        $this->hasErrorOutput = false;

        if ($cleanInput) {
            $this->setInput(null);
            $this->setPreviousState(null);
        }
    }

    public function getProcessConfiguration(): ProcessConfiguration
    {
        return $this->processConfiguration;
    }

    public function getProcessHistory(): ProcessHistory
    {
        return $this->processHistory;
    }

    public function getTaskConfiguration(): TaskConfiguration
    {
        return $this->taskConfiguration;
    }

    public function setTaskConfiguration(TaskConfiguration $taskConfiguration): void
    {
        $this->taskConfiguration = $taskConfiguration;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        $this->input = $input;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }

    public function setOutput(mixed $output): void
    {
        $this->output = $output;
    }

    public function getErrorOutput(): mixed
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(mixed $errorOutput): void
    {
        $this->hasErrorOutput = true;
        $this->errorOutput = $errorOutput;
    }

    public function hasErrorOutput(): bool
    {
        return $this->hasErrorOutput;
    }

    public function stop(\Throwable $e = null): void
    {
        if ($e) {
            $this->setException($e);
        }
        $this->setStopped(true);
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function setStopped(bool $stopped): void
    {
        $this->stopped = $stopped;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function setException(\Throwable $exception = null): void
    {
        $this->exception = $exception;
    }

    public function getErrorContext(): array
    {
        return $this->errorContext;
    }

    public function setErrorContext(array $errorContext): void
    {
        $this->errorContext = $errorContext;
    }

    public function addErrorContextValue(string|int $key, string|int|array $value): void
    {
        $this->errorContext[$key] = $value;
    }

    public function removeErrorContext(string|int $key): void
    {
        unset($this->errorContext[$key]);
    }

    public function getReturnCode(): int
    {
        return $this->returnCode ?? 0;
    }

    public function setReturnCode(int $returnCode): void
    {
        $this->returnCode = $returnCode;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function setSkipped(bool $skipped): void
    {
        $this->skipped = $skipped;
    }

    public function getPreviousState(): ?self
    {
        return $this->previousState;
    }

    public function setPreviousState(?self $previousState): void
    {
        $this->previousState = $previousState;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!\in_array($status, self::STATUS, true)) {
            throw new \UnexpectedValueException("Unknown status {$status}");
        }

        $this->status = $status;
    }

    public function isResolved(): bool
    {
        return self::STATUS_RESOLVED === $this->status;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        if ($this->context) {
            throw new \RuntimeException('Once defined, context is immutable');
        }

        $this->context = $context;
    }

    public function getContextualizedOptions(): ?array
    {
        if (!$this->contextualizedOptions) {
            $options = $this->getTaskConfiguration()
                ->getOptions();
            $this->contextualizedOptions = $this->contextualOptionResolver->contextualizeOptions(
                $options,
                $this->context
            );
        }

        return $this->contextualizedOptions;
    }

    public function getContextualizedOption(string $code, mixed $default = null): mixed
    {
        $contextualizedOptions = $this->getContextualizedOptions();
        if (\array_key_exists($code, $contextualizedOptions)) {
            return $contextualizedOptions[$code];
        }

        return $default;
    }
}
