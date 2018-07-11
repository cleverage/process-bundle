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

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use Psr\Log\LogLevel;

/**
 * History element for a task
 *
 * @author     Valentin Clavreul <vclavreul@clever-age.com>
 * @author     Vincent Chalnot <vchalnot@clever-age.com>
 */
class TaskHistory
{

    /**
     * @var ProcessHistory
     */
    protected $processHistory;

    /**
     * @var string
     */
    protected $taskCode;

    /**
     * @var \DateTime
     */
    protected $loggedAt;

    /**
     * @var int
     */
    protected $level = LogLevel::ERROR;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var string
     */
    protected $reference;

    /**
     * @var string
     */
    protected $message;

    /**
     * @param TaskConfiguration $taskConfiguration
     */
    public function __construct(TaskConfiguration $taskConfiguration)
    {
        $this->taskCode = $taskConfiguration->getCode();
        $this->loggedAt = new \DateTime();
    }

    /**
     * @return ProcessHistory
     */
    public function getProcessHistory(): ProcessHistory
    {
        return $this->processHistory;
    }

    /**
     * @param ProcessHistory $processHistory
     */
    public function setProcessHistory(ProcessHistory $processHistory)
    {
        $this->processHistory = $processHistory;
    }

    /**
     * @return string
     */
    public function getTaskCode(): string
    {
        return $this->taskCode;
    }

    /**
     * @return \DateTime
     */
    public function getLoggedAt(): \DateTime
    {
        return $this->loggedAt;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @param string $level
     */
    public function setLevel(string $level)
    {
        $this->level = $level;
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
    public function setContext(array $context = null)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference(string $reference = null)
    {
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message = null)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $reference = $this->getProcessHistory()->getProcessCode().'/'.$this->getTaskCode();
        $time = $this->getLoggedAt()->format(\DateTime::ATOM);

        return $reference.': '.$time;
    }
}
