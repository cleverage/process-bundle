<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event object for process start/stop/fail
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class ProcessEvent extends Event
{

    const EVENT_PROCESS_STARTED = 'cleverage_process.start';
    const EVENT_PROCESS_ENDED = 'cleverage_process.end';
    const EVENT_PROCESS_FAILED = 'cleverage_process.fail';

    /** @var string */
    protected $processCode;

    /** @var mixed */
    protected $processInput;

    /** @var mixed */
    protected $processOutput;

    /** @var array */
    protected $processContext;

    /** @var \Throwable|null */
    protected $processError;

    /**
     * ProcessEvent constructor.
     *
     * @param string          $processCode
     * @param mixed           $processInput
     * @param array           $processContext
     * @param mixed           $processOutput
     * @param \Throwable|null $processError
     */
    public function __construct(
        string $processCode,
        $processInput = null,
        array $processContext = [],
        $processOutput = null,
        \Throwable $processError = null
    ) {
        $this->processCode = $processCode;
        $this->processInput = $processInput;
        $this->processOutput = $processOutput;
        $this->processContext = $processContext;
        $this->processError = $processError;
    }

    /**
     * @return string
     */
    public function getProcessCode(): string
    {
        return $this->processCode;
    }

    /**
     * @return mixed
     */
    public function getProcessInput()
    {
        return $this->processInput;
    }

    /**
     * @return mixed
     */
    public function getProcessOutput()
    {
        return $this->processOutput;
    }

    /**
     * @return array
     */
    public function getProcessContext(): array
    {
        return $this->processContext;
    }

    /**
     * @return \Throwable|null
     */
    public function getProcessError(): ?\Throwable
    {
        return $this->processError;
    }

}
