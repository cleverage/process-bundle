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

namespace CleverAge\ProcessBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * The cleverage:process:execute command receiving SIGTERM during a process, in a PHP process of its own (see
 * signal_during_a_process.php): the process is stopped before its next task, instead of the command exiting right away.
 */
#[CoversNothing]
#[RequiresPhpExtension('pcntl')]
#[RequiresPhpExtension('posix')]
class SignalTest extends TestCase
{
    public function testSignalStopsTheProcessBeforeItsNextTask(): void
    {
        $process = new Process([\PHP_BINARY, __DIR__.'/signal_during_a_process.php'], null, ['SHELL_VERBOSITY' => '0']);
        $process->start();
        $pid = $process->getPid();
        try {
            $process->wait();
        } finally {
            // Run directory of the test application in the PHP process
            (new Filesystem())->remove(sys_get_temp_dir().'/process_bundle_tests_'.$pid);
        }

        self::assertSame(1, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        self::assertStringContainsString('Process test.run has been stopped before task constant.', $process->getErrorOutput());
        // The process output is not printed
        self::assertStringNotContainsString('Hello world', $process->getOutput());
    }
}
