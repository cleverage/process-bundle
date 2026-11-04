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

namespace CleverAge\ProcessBundle\Tests\EventListener;

use CleverAge\ProcessBundle\EventListener\SignalStopChecker;
use CleverAge\ProcessBundle\Model\ProcessState;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

#[CoversClass(SignalStopChecker::class)]
#[RequiresPhpExtension('pcntl')]
class SignalStopCheckerTest extends TestCase
{
    public function testSignalWithoutRunningProcessExits(): void
    {
        $checker = new SignalStopChecker();
        $event = $this->createEvent(\SIGTERM);

        $checker->onConsoleSignal($event);

        self::assertSame(0, $event->getExitCode());
        self::assertFalse($checker->shouldStop($this->createStub(ProcessState::class)));
    }

    public function testSignalDuringAProcessStopsIt(): void
    {
        $checker = new SignalStopChecker(new Logger('cleverage_process', [$logs = new TestHandler()]));
        $checker->onProcessStart();
        $state = $this->createStub(ProcessState::class);
        self::assertFalse($checker->shouldStop($state));

        $event = $this->createEvent(\SIGTERM);
        $checker->onConsoleSignal($event);

        self::assertFalse($event->getExitCode(), 'The command does not exit');
        self::assertTrue($checker->shouldStop($state));
        self::assertTrue($checker->shouldStop($state), 'Until the process has failed');
        self::assertCount(1, $logs->getRecords(), 'Logged once');
        self::assertTrue($logs->hasWarningThatContains('Signal SIGTERM received: the process is stopped before its next task'));

        // A second signal exits right away
        $secondEvent = $this->createEvent(\SIGINT);
        $checker->onConsoleSignal($secondEvent);
        self::assertSame(0, $secondEvent->getExitCode());
    }

    /**
     * The console application creates one event per signal and dispatches it again each time the signal is received.
     */
    public function testSameSignalReceivedAgainExits(): void
    {
        $checker = new SignalStopChecker();
        $checker->onProcessStart();
        $event = $this->createEvent(\SIGINT);

        $checker->onConsoleSignal($event);
        self::assertFalse($event->getExitCode(), 'The command does not exit');

        $checker->onConsoleSignal($event);
        self::assertSame(0, $event->getExitCode(), 'A second signal exits right away');

        // After a reset (e.g. the next message of a Messenger worker), the same event stops the next process again
        $checker->reset();
        $checker->onProcessStart();
        $checker->onConsoleSignal($event);
        self::assertFalse($event->getExitCode());
        $checker->onConsoleSignal($event);
        self::assertSame(0, $event->getExitCode());
    }

    public function testSameSignalReceivedAgainWithoutRunningProcessExits(): void
    {
        $checker = new SignalStopChecker();
        $checker->onProcessStart();
        $event = $this->createEvent(\SIGTERM);
        $checker->onConsoleSignal($event);
        $checker->reset();

        $checker->onConsoleSignal($event);

        self::assertSame(0, $event->getExitCode());
    }

    /**
     * Inside another console application handling the signal, the outer application decides if the process exits.
     */
    public function testSameSignalReceivedAgainKeepsAnAbortedExit(): void
    {
        $checker = new SignalStopChecker();
        $checker->onProcessStart();
        $event = new ConsoleSignalEvent(new Command('test'), new ArrayInput([]), new NullOutput(), \SIGTERM, false);

        $checker->onConsoleSignal($event);
        $checker->onConsoleSignal($event);

        self::assertFalse($event->getExitCode());
    }

    /**
     * The stop request is kept for the next processes of the command, until the services are reset.
     */
    public function testStopRequestIsKeptUntilReset(): void
    {
        $checker = new SignalStopChecker(new Logger('cleverage_process', [$logs = new TestHandler()]));
        $state = $this->createStub(ProcessState::class);
        $checker->onProcessStart();
        $checker->onProcessStart(); // sub-process
        $checker->onConsoleSignal($this->createEvent(\SIGINT));
        self::assertTrue($checker->shouldStop($state));
        $checker->onProcessEnd();
        $checker->onProcessEnd();
        $checker->onProcessEnd(); // more ends than starts (e.g. a failure of a listener of the end)

        $checker->onProcessStart();
        self::assertTrue($checker->shouldStop($state));
        self::assertCount(2, $logs->getRecords(), 'Logged once per process');
        self::assertTrue($logs->hasWarningThatContains('Signal SIGINT received'));

        $checker->reset();
        self::assertFalse($checker->shouldStop($state));
        $event = $this->createEvent(\SIGINT);
        $checker->onConsoleSignal($event);
        self::assertSame(0, $event->getExitCode(), 'No running process after the reset');
    }

    public function testOtherSignalsAreIgnored(): void
    {
        $checker = new SignalStopChecker();
        $checker->onProcessStart();
        $event = $this->createEvent(\SIGUSR1);

        $checker->onConsoleSignal($event);

        self::assertSame(0, $event->getExitCode());
        self::assertFalse($checker->shouldStop($this->createStub(ProcessState::class)));
    }

    private function createEvent(int $signal): ConsoleSignalEvent
    {
        return new ConsoleSignalEvent(new Command('test'), new ArrayInput([]), new NullOutput(), $signal);
    }
}
