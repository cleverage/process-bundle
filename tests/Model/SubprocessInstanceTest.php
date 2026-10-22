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

namespace CleverAge\ProcessBundle\Tests\Model;

use CleverAge\ProcessBundle\Filesystem\TemporaryFile;
use CleverAge\ProcessBundle\Model\SubprocessInstance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(SubprocessInstance::class)]
#[UsesClass(TemporaryFile::class)]
class SubprocessInstanceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/subprocess_instance_test_'.uniqid('', true);
        mkdir($this->projectDir.'/bin', 0o777, true);
        touch($this->projectDir.'/bin/console');
    }

    protected function tearDown(): void
    {
        unlink($this->projectDir.'/bin/console');
        rmdir($this->projectDir.'/bin');
        rmdir($this->projectDir);
    }

    public function testCommandLine(): void
    {
        $subprocess = (new SubprocessInstance($this->projectDir.'/bin/console', 'child', 'input', ['key' => 'value'], [], 'prod'))
            ->buildProcess();

        $commandLine = $subprocess->getProcess()->getCommandLine();
        $this->assertStringContainsString("'{$this->projectDir}/bin/console' '--env=prod' 'cleverage:process:execute' '--input-from-stdin'", $commandLine);
        $this->assertStringEndsWith("'--context=key:value' 'child'", $commandLine);
        $this->assertSame('input', $subprocess->getProcess()->getInput());
    }

    public function testDefaultEnvironmentOfTheConsoleWithoutEnvironment(): void
    {
        $subprocess = (new SubprocessInstance($this->projectDir.'/bin/console', 'child', null))->buildProcess();

        $this->assertStringNotContainsString('--env', $subprocess->getProcess()->getCommandLine());
    }

    public function testJsonBufferIsWrittenInTheTemporaryDirectory(): void
    {
        $subprocess = (new SubprocessInstance($this->projectDir.'/bin/console', 'child', null, [], [SubprocessInstance::OPTION_JSON_BUFFERING => true]))
            ->buildProcess();

        $this->assertMatchesRegularExpression(
            '#\'--output=('.preg_quote(sys_get_temp_dir(), '#').'/cdm_buffer_[0-9a-f]{32}\.json-stream)\' \'--output-format=json-stream\'#',
            $subprocess->getProcess()->getCommandLine()
        );
        preg_match('#--output=([^\']+)#', $subprocess->getProcess()->getCommandLine(), $matches);
        // Created before the sub-process writes into it, readable by its owner only
        $this->assertFileExists($matches[1]);
        $this->assertSame(0o600, fileperms($matches[1]) & 0o777);
        unlink($matches[1]);
    }

    public function testMissingConsoleThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to resolve path to symfony console '{$this->projectDir}/missing'");

        (new SubprocessInstance($this->projectDir.'/missing', 'child', null))->buildProcess();
    }

    public function testPassingTheKernelIsDeprecated(): void
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDir);
        $kernel->method('getEnvironment')->willReturn('prod');
        $kernel->method('getLogDir')->willReturn($this->projectDir.'/var/log');
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $subprocess = new SubprocessInstance($kernel, 'child', null);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(
            ['Since cleverage/process-bundle 5.4: Passing a "Symfony\Component\HttpKernel\KernelInterface" to "CleverAge\ProcessBundle\Model\SubprocessInstance::__construct()" is deprecated, pass the path of the console and the environment instead. The argument will only accept a string in v6.0.'],
            $deprecations
        );
        $commandLine = $subprocess->buildProcess()->getProcess()->getCommandLine();
        $this->assertStringContainsString("'{$this->projectDir}/bin/console' '--env=prod'", $commandLine);
        // The log directory of the kernel is no longer created
        $this->assertDirectoryDoesNotExist($this->projectDir.'/var');
    }
}
