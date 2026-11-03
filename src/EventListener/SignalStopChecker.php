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

namespace CleverAge\ProcessBundle\EventListener;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\ProcessStopCheckerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Stops the running process before its next task when the console command executing it (cleverage:process:execute,
 * messenger:consume...) receives SIGTERM or SIGINT, instead of exiting right away: the tasks are finalized and the
 * process fails with a ProcessStoppedException. A second signal exits right away, as without a running process.
 *
 * The stop request is kept until the services are reset (e.g. between two messages of a Messenger worker, which stops
 * on the signal too): the next processes executed by the command are stopped as well, as a signal received at the end
 * of a process is not lost.
 *
 * The console signals are dispatched by the Symfony console application when the pcntl extension is installed.
 *
 * @internal
 */
final class SignalStopChecker implements ProcessStopCheckerInterface, ResetInterface
{
    private int $runningProcesses = 0;

    private ?int $signal = null;

    private bool $stopLogged = false;

    public function __construct(private readonly ?LoggerInterface $logger = null)
    {
    }

    public function onConsoleSignal(ConsoleSignalEvent $event): void
    {
        $signal = $event->getHandlingSignal();
        if (!\in_array($signal, $this->getStopSignals(), true)) {
            return;
        }
        if (0 === $this->runningProcesses || null !== $this->signal) {
            // No process to stop, or a second signal: the default behavior (exit, unless another listener aborts it)
            return;
        }
        // No logging here: the signal may interrupt any code, e.g. a logger writing a record
        $this->signal = $signal;
        $event->abortExit();
    }

    /**
     * The start of a process (sub-processes included).
     */
    public function onProcessStart(): void
    {
        if (0 === $this->runningProcesses) {
            $this->stopLogged = false;
        }
        ++$this->runningProcesses;
    }

    /**
     * The end or the failure of a process (sub-processes included).
     */
    public function onProcessEnd(): void
    {
        $this->runningProcesses = max(0, $this->runningProcesses - 1);
    }

    public function shouldStop(ProcessState $state): bool
    {
        if (null === $this->signal) {
            return false;
        }
        if (!$this->stopLogged) {
            $this->stopLogged = true;
            $this->logger?->warning(\sprintf(
                'Signal %s received: the process is stopped before its next task (send it again to exit right away).',
                \SIGTERM === $this->signal ? 'SIGTERM' : 'SIGINT'
            ));
        }

        return true;
    }

    public function reset(): void
    {
        $this->runningProcesses = 0;
        $this->signal = null;
        $this->stopLogged = false;
    }

    /**
     * SIGTERM (e.g. docker stop, Kubernetes, systemd, supervisord) and SIGINT (Ctrl+C): their constants are defined by
     * the pcntl extension.
     *
     * @return list<int>
     */
    private function getStopSignals(): array
    {
        return \defined('SIGTERM') && \defined('SIGINT') ? [\SIGTERM, \SIGINT] : [];
    }
}
