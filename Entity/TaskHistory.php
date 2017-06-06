<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Entity;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LogLevel;

/**
 * History element for a task
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 *
 * @ORM\Table(name="clever_task_history", indexes={
 *     @ORM\Index(name="task_code", columns={"task_code"}),
 *     @ORM\Index(name="logged_at", columns={"logged_at"}),
 *     @ORM\Index(name="level", columns={"level"})
 * })
 * @ORM\Entity(repositoryClass="CleverAge\ProcessBundle\Entity\TaskHistoryRepository")
 */
class TaskHistory
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ProcessHistory
     *
     * @ORM\ManyToOne(targetEntity="CleverAge\ProcessBundle\Entity\ProcessHistory", inversedBy="taskHistories")
     * @ORM\JoinColumn(name="process_history_id", referencedColumnName="id", onDelete="cascade")
     */
    protected $processHistory;

    /**
     * @var string
     *
     * @ORM\Column(name="task_code", type="string")
     */
    protected $taskCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logged_at", type="datetime")
     */
    protected $loggedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="string", length=16)
     */
    protected $level = LogLevel::ERROR;

    /**
     * @var array
     *
     * @ORM\Column(name="context", type="json_array", nullable=true)
     */
    protected $context;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", nullable=true)
     */
    protected $reference;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    protected $message;

    /**
     * TaskHistory constructor.
     *
     * @param ProcessHistory    $processHistory
     * @param TaskConfiguration $taskConfiguration
     */
    public function __construct(ProcessHistory $processHistory, TaskConfiguration $taskConfiguration)
    {
        $this->processHistory = $processHistory;
        $this->taskCode = $taskConfiguration->getCode();
        $this->loggedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ProcessHistory
     */
    public function getProcessHistory(): ProcessHistory
    {
        return $this->processHistory;
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
}
