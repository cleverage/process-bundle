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

namespace CleverAge\ProcessBundle\Model;

/**
 * Allows to stop a running process from the outside (e.g. on a request made from a UI).
 *
 * The process manager asks every checker before each task execution (once per item for the iterable tasks): an
 * implementation must be fast, e.g. check an external storage at most every few seconds. When a checker returns true,
 * the process is stopped before executing the task, as a task error with the "stop" error strategy, with a
 * ProcessStoppedException.
 *
 * Services implementing this interface are autoconfigured with the "cleverage_process.stop_checker" tag.
 */
interface ProcessStopCheckerInterface
{
    /**
     * The state is the one of the task about to be executed, which may belong to a sub-process
     * (ProcessExecutorTask): keep returning true until the process has failed.
     */
    public function shouldStop(ProcessState $state): bool;
}
