<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Model;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;

/**
 * Used to pass information between tasks
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessState
{
    public const STATUS = [self::STATUS_NEW, self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_RESOLVED];
    public const STATUS_NEW = 'new';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_RESOLVED = 'resolved';

    /** @var ProcessConfiguration */
    protected $processConfiguration;

    /** @var ProcessHistory */
    protected $processHistory;

    /** @var TaskConfiguration */
    protected $taskConfiguration;

    /** @var mixed */
    protected $input;

    /** @var mixed */
    protected $output;

    /** @var mixed */
    protected $errorOutput;

    /** @var boolean */
    protected $hasErrorOutput = false;

    /** @var bool */
    protected $stopped = false;

    /** @var \Throwable */
    protected $exception;

    /** @var array */
    protected $errorContext = [];

    /** @var int */
    protected $returnCode;

    /** @var bool */
    protected $skipped;

    /** @var array */
    protected $context;

    /** @var ContextualOptionResolver */
    protected $contextualOptionResolver;

    /** @var array */
    protected $contextualizedOptions;

    /** @var ProcessState|null */
    protected $previousState;

    /** @var string */
    protected $status = self::STATUS_NEW;

    /**
     * @param ProcessConfiguration $processConfiguration
     * @param ProcessHistory       $processHistory
     */
    public function __construct(ProcessConfiguration $processConfiguration, ProcessHistory $processHistory)
    {
        $this->processConfiguration = $processConfiguration;
        $this->processHistory = $processHistory;
    }

    /**
     * @param ContextualOptionResolver $contextualOptionResolver
     */
    public function setContextualOptionResolver(ContextualOptionResolver $contextualOptionResolver): void
    {
        $this->contextualOptionResolver = $contextualOptionResolver;
    }

    /**
     * Clone the current object and keep a back reference
     *
     * @return ProcessState
     */
    public function duplicate(): ProcessState
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

    /**
     * @return ProcessConfiguration
     */
    public function getProcessConfiguration(): ProcessConfiguration
    {
        return $this->processConfiguration;
    }

    /**
     * @return ProcessHistory
     */
    public function getProcessHistory(): ProcessHistory
    {
        return $this->processHistory;
    }

    /**
     * @return TaskConfiguration
     */
    public function getTaskConfiguration(): TaskConfiguration
    {
        return $this->taskConfiguration;
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     */
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

    /**
     * @param mixed $input
     */
    public function setInput($input): void
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

    /**
     * @param mixed $output
     */
    public function setOutput($output): void
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
     * @param mixed $error
     *
     * @deprecated Use setErrorOutput instead
     */
    public function setError($error): void
    {
        @trigger_error('Deprecated method, use setErrorOutput instead', E_USER_DEPRECATED);

        $this->setErrorOutput($error);
    }

    /**
     * @return bool
     *
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

    /**
     * @param mixed $errorOutput
     */
    public function setErrorOutput($errorOutput): void
    {
        $this->hasErrorOutput = true;
        $this->errorOutput = $errorOutput;
    }

    /**
     * @return bool
     */
    public function hasErrorOutput(): bool
    {
        return $this->hasErrorOutput;
    }

    /**
     * @param \Throwable $e
     */
    public function stop(\Throwable $e = null): void
    {
        if ($e) {
            $this->setException($e);
        }
        $this->setStopped(true);
    }

    /**
     * @return boolean
     */
    public function isStopped(): bool
    {
        return $this->stopped;
    }

    /**
     * @param boolean $stopped
     */
    public function setStopped(bool $stopped): void
    {
        $this->stopped = $stopped;
    }

    /**
     * @return \Throwable|null
     */
    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    /**
     * @param \Throwable|null $exception
     */
    public function setException(\Throwable $exception = null): void
    {
        $this->exception = $exception;
    }

    /**
     * @return array
     */
    public function getErrorContext(): array
    {
        return $this->errorContext;
    }

    /**
     * @param array $errorContext
     */
    public function setErrorContext(array $errorContext): void
    {
        $this->errorContext = $errorContext;
    }

    /**
     * @param string|int       $key
     * @param string|int|array $value
     */
    public function addErrorContextValue($key, $value): void
    {
        $this->errorContext[$key] = $value;
    }

    /**
     * @param string|int $key
     */
    public function removeErrorContext($key): void
    {
        unset($this->errorContext[$key]);
    }

    /**
     * @return int
     */
    public function getReturnCode(): int
    {
        if (null !== $this->returnCode) {
            return $this->returnCode;
        }

        return 0;
    }

    /**
     * @param int $returnCode
     */
    public function setReturnCode(int $returnCode): void
    {
        $this->returnCode = $returnCode;
    }

    /**
     * @return bool
     */
    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    /**
     * @param bool $skipped
     */
    public function setSkipped(bool $skipped): void
    {
        $this->skipped = $skipped;
    }

    /**
     * @return ProcessState|null
     */
    public function getPreviousState(): ?ProcessState
    {
        return $this->previousState;
    }

    /**
     * @param ProcessState $previousState
     */
    public function setPreviousState($previousState): void
    {
        $this->previousState = $previousState;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @throws \UnexpectedValueException
     */
    public function setStatus(string $status): void
    {
        if (!\in_array($status, self::STATUS, true)) {
            throw new \UnexpectedValueException("Unknown status {$status}");
        }

        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     *
     * @throws \RuntimeException
     */
    public function setContext(array $context): void
    {
        if ($this->context) {
            throw new \RuntimeException('Once defined, context is immutable');
        }

        $this->context = $context;
    }

    /**
     * @return array|null
     */
    public function getContextualizedOptions(): ?array
    {
        if (!$this->contextualizedOptions) {
            $options = $this->getTaskConfiguration()->getOptions();
            $this->contextualizedOptions = $this->contextualOptionResolver->contextualizeOptions(
                $options,
                $this->context
            );
        }

        return $this->contextualizedOptions;
    }

    /**
     * @param string $code
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getContextualizedOption($code, $default = null)
    {
        $contextualizedOptions = $this->getContextualizedOptions();
        if (array_key_exists($code, $contextualizedOptions)) {
            return $contextualizedOptions[$code];
        }

        return $default;
    }

    /**
     * @return array
     *
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
