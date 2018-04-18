<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Model;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Entity\ProcessHistory;
use CleverAge\ProcessBundle\Entity\TaskHistory;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Used to pass information between tasks
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessState
{

    const STATUS = [self::STATUS_NEW, self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_RESOLVED];
    const STATUS_NEW = 'new';
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_RESOLVED = 'resolved';

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
    protected $error;

    /** @var TaskHistory[] */
    protected $taskHistories = [];

    /** @var bool */
    protected $stopped = false;

    /** @var \Throwable */
    protected $exception;

    /** @var array */
    protected $errorContext = [];

    /** @var OutputInterface */
    protected $consoleOutput;

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
    public function duplicate()
    {
        $newState = clone $this;
        $newState->setPreviousState($this);

        return $newState;
    }

    /**
     * @return ProcessConfiguration
     */
    public function getProcessConfiguration()
    {
        return $this->processConfiguration;
    }

    /**
     * @return ProcessHistory
     */
    public function getProcessHistory()
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
    public function setTaskConfiguration(TaskConfiguration $taskConfiguration)
    {
        $this->taskConfiguration = $taskConfiguration;
    }

    /**
     * @param string $message
     * @param string $level
     * @param string $reference
     * @param array  $context
     */
    public function log(string $message, string $level = LogLevel::ERROR, string $reference = null, array $context = [])
    {
        $taskHistory = new TaskHistory($this->getTaskConfiguration());
        $taskHistory->setMessage($message);
        $taskHistory->setLevel($level);
        $taskHistory->setReference($reference);
        $taskHistory->setContext(array_merge($this->getErrorContext(), $context));

        $this->taskHistories[] = $taskHistory;
    }

    /**
     * @return TaskHistory[]
     */
    public function getTaskHistories(): array
    {
        return $this->taskHistories;
    }

    /**
     * Cleanup log
     */
    public function clearTaskHistories()
    {
        $this->taskHistories = [];
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
    public function setInput($input)
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
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @TODO use a flag in setter instead of null check
     * @return bool
     */
    public function hasError()
    {
        return $this->error !== null;
    }

    /**
     * @param \Throwable $e
     */
    public function stop(\Throwable $e = null)
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
    public function setStopped(bool $stopped)
    {
        $this->stopped = $stopped;
    }

    /**
     * @return \Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Throwable $exception
     */
    public function setException(\Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return array
     */
    public function getErrorContext()
    {
        return $this->errorContext;
    }

    /**
     * @param array $errorContext
     */
    public function setErrorContext(array $errorContext)
    {
        $this->errorContext = $errorContext;
    }

    /**
     * @param string|int       $key
     * @param string|int|array $value
     */
    public function addErrorContextValue($key, $value)
    {
        $this->errorContext[$key] = $value;
    }

    /**
     * @param string|int $key
     */
    public function removeErrorContext($key)
    {
        unset($this->errorContext[$key]);
    }

    /**
     * @return OutputInterface
     */
    public function getConsoleOutput()
    {
        return $this->consoleOutput;
    }

    /**
     * @param OutputInterface $consoleOutput
     */
    public function setConsoleOutput(OutputInterface $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * @return int
     */
    public function getReturnCode()
    {
        if (null !== $this->returnCode) {
            return $this->returnCode;
        }

        return 0;
    }

    /**
     * @param int $returnCode
     */
    public function setReturnCode(int $returnCode)
    {
        $this->returnCode = $returnCode;
    }

    /**
     * @return bool
     */
    public function isSkipped()
    {
        return $this->skipped;
    }

    /**
     * @param bool $skipped
     */
    public function setSkipped(bool $skipped)
    {
        $this->skipped = $skipped;
    }

    /**
     * @return ProcessState
     */
    public function getPreviousState()
    {
        return $this->previousState;
    }

    /**
     * @param ProcessState $previousState
     */
    public function setPreviousState($previousState)
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
     */
    public function setStatus(string $status)
    {
        if (!in_array($status, self::STATUS)) {
            throw new \UnexpectedValueException("Unknown status {$status}");
        }

        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isResolved()
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
     */
    public function setContext(array $context): void
    {
        if ($this->context) {
            throw new \RuntimeException('Once defined, context is immutable');
        }

        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getContextualizedOptions()
    {
        if (!$this->contextualizedOptions) {
            $options = $this->getTaskConfiguration()->getOptions();
            $this->contextualizedOptions = $this->contextualOptionResolver->contextualizeOptions($options, $this->context);
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
}
