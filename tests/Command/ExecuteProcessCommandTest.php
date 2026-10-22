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

namespace CleverAge\ProcessBundle\Tests\Command;

use CleverAge\ProcessBundle\Command\ExecuteProcessCommand;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Event\ConsoleProcessEvent;
use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ExecuteProcessCommand::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(ConsoleProcessEvent::class)]
#[UsesClass(JsonStreamFile::class)]
class ExecuteProcessCommandTest extends TestCase
{
    private string $outputFile;

    protected function setUp(): void
    {
        $this->outputFile = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('execute_process_command_test_', true).'.json-stream';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testUnknownProcessCodesAreDetectedBeforeAnyExecution(): void
    {
        $tester = $this->createTester($this->never());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown process test.unknown');

        $tester->execute(['processCodes' => ['test.process', 'test.unknown']]);
    }

    public function testAnInvalidOutputFormatIsDetectedBeforeAnyExecution(): void
    {
        $tester = $this->createTester($this->never());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle data output with format 'xml'");

        $tester->execute(['processCodes' => ['test.process'], '--output-format' => 'xml']);
    }

    public function testTheDumpFormatIsOnlyForTheStandardOutput(): void
    {
        $tester = $this->createTester($this->never());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle data output with format 'dump'");

        $tester->execute(['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'dump']);
    }

    public function testTheJsonOutputIsPrintedWithoutVerbosity(): void
    {
        $tester = $this->createTester($this->once(), ['foo' => 'bar']);

        self::assertSame(0, $tester->execute(['processCodes' => ['test.process'], '--output-format' => 'json-stream']));
        self::assertStringContainsString('{"foo":"bar"}', $tester->getDisplay());
    }

    public function testAnArrayOutputIsStoredInTheFile(): void
    {
        $tester = $this->createTester($this->once(), ['foo' => 'bar']);

        self::assertSame(0, $tester->execute(['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'json-stream']));
        self::assertSame('{"foo":"bar"}', trim((string) file_get_contents($this->outputFile)));
    }

    public function testANonArrayOutputIsReportedInsteadOfBeingSilentlyLost(): void
    {
        $tester = $this->createTester($this->once(), 'scalar');

        self::assertSame(0, $tester->execute(
            ['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'json-stream'],
            ['capture_stderr_separately' => true],
        ));
        self::assertFileDoesNotExist($this->outputFile);
        self::assertStringContainsString(
            "Output not stored in '{$this->outputFile}': the JSON stream format requires an array, string given",
            $tester->getErrorOutput()
        );
    }

    private function createTester(\PHPUnit\Framework\MockObject\Rule\InvocationOrder $executions, mixed $result = null): CommandTester
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects($executions)->method('execute')->willReturn($result);
        $registry = new ProcessConfigurationRegistry(
            [
                'test.process' => [
                    'options' => [],
                    'entry_point' => null,
                    'end_point' => null,
                    'description' => '',
                    'help' => '',
                    'public' => true,
                    'tasks' => [],
                ],
            ],
            'stop'
        );

        return new CommandTester(new ExecuteProcessCommand($processManager, new EventDispatcher(), $registry));
    }
}
