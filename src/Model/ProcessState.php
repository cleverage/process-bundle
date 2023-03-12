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
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

/**
 * Used to pass information between tasks
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

    /**
     * @var mixed
     */
    protected $input;

    /**
     * @var mixed
     */
    protected $output;

    /**
     * @var mixed
     */
    protected $errorOutput;

    /**
     * @var boolean
     */
    protected $hasErrorOutput = false;

    /**
     * @var bool
     */
    protected $stopped = false;

    protected ?Throwable $exception = null;

    /**
     * @var array
     */
    protected $errorContext = [];

    /**
     * @var int
     */
    protected $returnCode;

    protected bool $skipped;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var ContextualOptionResolver
     */
    protected $contextualOptionResolver;

    /**
     * @var array
     */
    protected $contextualizedOptions;

    protected ?\CleverAge\ProcessBundle\Model\ProcessState $previousState = null;

    /**
     * @var string
     */
    protected $status = self::STATUS_NEW;

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
     * Clone the current object and keep a back reference
     */
    public function duplicate(): self
    {
        $newState = clone $this;
        $newState->setPreviousState($this);

        return $newState;
    }

    /**
     * Reset the state object
     * To be used before execution
     *
     * @param bool $cleanInput
     */
    public function reset($cleanInput): void
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

    /**
     * @return mixed
     */
    public function getInput()
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        $this->input = $input;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput(mixed $output): void
    {
        $this->output = $output;
    }

    /**
     * @return mixed
     *
     * @deprecated Use getErrorOutput instead
     */
    public function getError()
    {
        @trigger_error('Deprecated method, use getErrorOutput instead', E_USER_DEPRECATED);

        return $this->getErrorOutput();
    }

    /**
     * @deprecated Use setErrorOutput instead
     */
    public function setError(mixed $error): void
    {
        @trigger_error('Deprecated method, use setErrorOutput instead', E_USER_DEPRECATED);

        $this->setErrorOutput($error);
    }

    /**
     * @deprecated Use hasErrorOutput instead
     */
    public function hasError(): bool
    {
        @trigger_error('Deprecated method, use hasErrorOutput instead', E_USER_DEPRECATED);

        return $this->hasErrorOutput();
    }

    /**
     * @return mixed
     */
    public function getErrorOutput()
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

    public function stop(Throwable $e = null): void
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

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function setException(Throwable $exception = null): void
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
        if ($this->returnCode !== null) {
            return $this->returnCode;
        }

        return 0;
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
        if (! \in_array($status, self::STATUS, true)) {
            throw new UnexpectedValueException("Unknown status {$status}");
        }

        $this->status = $status;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        if ($this->context) {
            throw new RuntimeException('Once defined, context is immutable');
        }

        $this->context = $context;
    }

    public function getContextualizedOptions(): ?array
    {
        if (! $this->contextualizedOptions) {
            $options = $this->getTaskConfiguration()
                ->getOptions();
            $this->contextualizedOptions = $this->contextualOptionResolver->contextualizeOptions(
                $options,
                $this->context
            );
        }

        return $this->contextualizedOptions;
    }

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function getContextualizedOption($code, mixed $default = null)
    {
        $contextualizedOptions = $this->getContextualizedOptions();
        if (array_key_exists($code, $contextualizedOptions)) {
            return $contextualizedOptions[$code];
        }

        return $default;
    }

    /**
     * @deprecated Use monolog processors instead
     */
    public function getLogContext(): array
    {
        @trigger_error('Deprecated method, use monolog processors instead', E_USER_DEPRECATED);
        $context = [
            'process_id' => $this->processHistory->getId(),
            'process_code' => $this->processConfiguration->getCode(),
            'process_context' => $this->context,
            'task_code' => $this->taskConfiguration->getCode(),
            'task_service' => $this->taskConfiguration->getServiceReference(),
        ];

        if ($this->hasErrorOutput()) {
            $context['error'] = $this->getErrorOutput();
        }

        if ($this->exception) {
            $context['exception'] = $this->exception;
        }

        return $context;
    }
}
