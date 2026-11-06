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
use Psr\Log\AbstractLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ExecuteProcessCommand::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(ConsoleProcessEvent::class)]
#[UsesClass(JsonStreamFile::class)]
#[UsesClass(\CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases::class)]
#[UsesClass(\CleverAge\ProcessBundle\Configuration\DidYouMean::class)]
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

    public function testInvalidUtf8IsReplacedInTheJsonOutput(): void
    {
        $tester = $this->createTester($this->once(), ['name' => "Caf\xe9"]);

        self::assertSame(0, $tester->execute(['processCodes' => ['test.process'], '--output-format' => 'json-stream']));
        self::assertStringContainsString('{"name":"Caf\ufffd"}', $tester->getDisplay());
    }

    public function testAnOutputThatCannotBeEncodedAsJsonIsReportedAndTheNextProcessesAreExecuted(): void
    {
        $tester = $this->createTester($this->exactly(2), ['value' => \NAN]);

        self::assertSame(0, $tester->execute(
            ['processCodes' => ['test.process', 'test.process'], '--output-format' => 'json-stream'],
            ['capture_stderr_separately' => true],
        ));
        self::assertSame(2, substr_count($tester->getErrorOutput(), 'Output not printed: it cannot be encoded as JSON (Inf and NaN cannot be JSON encoded)'));
        self::assertSame(2, substr_count($tester->getDisplay(), "Process 'test.process' executed successfully"));
    }

    public function testAnArrayOutputIsStoredInTheFile(): void
    {
        $tester = $this->createTester($this->once(), ['foo' => 'bar']);

        self::assertSame(0, $tester->execute(['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'json-stream']));
        self::assertSame('{"foo":"bar"}', trim((string) file_get_contents($this->outputFile)));
    }

    /**
     * As on STDOUT: a JsonException was thrown (e.g. for the buffer of the process_launcher task).
     */
    public function testInvalidUtf8IsReplacedInTheFile(): void
    {
        $tester = $this->createTester($this->once(), ['name' => "Caf\xe9"]);

        self::assertSame(0, $tester->execute(['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'json-stream']));
        self::assertSame('{"name":"Caf\ufffd"}', trim((string) file_get_contents($this->outputFile)));
    }

    public function testAnOutputThatCannotBeEncodedAsJsonIsReportedInsteadOfBeingStored(): void
    {
        $tester = $this->createTester($this->once(), ['value' => \NAN]);

        self::assertSame(0, $tester->execute(
            ['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'json-stream'],
            ['capture_stderr_separately' => true],
        ));
        self::assertStringContainsString(
            "Output not stored in '{$this->outputFile}': it cannot be encoded as JSON (Inf and NaN cannot be JSON encoded)",
            $tester->getErrorOutput()
        );
    }

    /**
     * The output is encoded before opening the file: an existing file is kept as it is.
     */
    public function testAnOutputThatCannotBeEncodedAsJsonDoesNotTruncateTheFile(): void
    {
        file_put_contents($this->outputFile, '{"previous":"run"}'.\PHP_EOL);
        $tester = $this->createTester($this->once(), ['value' => \NAN]);

        self::assertSame(0, $tester->execute(
            ['processCodes' => ['test.process'], '--output' => $this->outputFile, '--output-format' => 'json-stream'],
            ['capture_stderr_separately' => true],
        ));
        self::assertStringContainsString("Output not stored in '{$this->outputFile}'", $tester->getErrorOutput());
        self::assertSame('{"previous":"run"}'.\PHP_EOL, file_get_contents($this->outputFile));
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

    /**
     * Only logged as a warning by the process manager, not displayed without -v: the most common mistake went unnoticed.
     */
    public function testAnInputIgnoredByAProcessWithoutEntryPointIsReported(): void
    {
        $tester = $this->createTester($this->exactly(2));

        self::assertSame(0, $tester->execute(
            ['processCodes' => ['test.process', 'test.entry_point'], '--input' => 'data.csv'],
            ['capture_stderr_separately' => true],
        ));
        self::assertSame(
            "The input is ignored by the process 'test.process': it has no entry_point",
            trim($tester->getErrorOutput())
        );
    }

    public function testNoWarningWithoutInput(): void
    {
        $tester = $this->createTester($this->once());

        self::assertSame(0, $tester->execute(['processCodes' => ['test.process']], ['capture_stderr_separately' => true]));
        self::assertSame('', $tester->getErrorOutput());
    }

    public function testTheProcessCodesAndTheOutputFormatsAreCompleted(): void
    {
        $processManager = $this->createStub(ProcessManager::class);
        $registry = new ProcessConfigurationRegistry(
            ['test.process' => ['options' => [], 'entry_point' => null, 'end_point' => null, 'description' => '', 'help' => '', 'public' => false, 'tasks' => []]],
            'stop'
        );
        $tester = new CommandCompletionTester(new ExecuteProcessCommand($processManager, new EventDispatcher(), $registry));

        // Private processes included: they can be executed
        self::assertSame(['test.process'], $tester->complete(['']));
        self::assertSame(['dump', 'json-stream'], $tester->complete(['test.process', '--output-format', '']));
    }

    /**
     * The configurations are not resolved: an invalid process is not logged (the log would be completed by the shell).
     */
    public function testTheCompletionDoesNotLogTheInvalidProcesses(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = (string) $message;
            }
        };
        $process = ['options' => [], 'entry_point' => null, 'end_point' => null, 'description' => '', 'help' => '', 'public' => true, 'tasks' => []];
        $registry = new ProcessConfigurationRegistry(['test.invalid' => ['entry_point' => 'missing'] + $process, 'test.valid' => $process], 'stop', [], $logger);
        $tester = new CommandCompletionTester(new ExecuteProcessCommand($this->createStub(ProcessManager::class), new EventDispatcher(), $registry));

        self::assertSame(['test.invalid', 'test.valid'], $tester->complete(['']));
        self::assertSame([], $logger->records);
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
                'test.entry_point' => [
                    'options' => [],
                    'entry_point' => 'first',
                    'end_point' => null,
                    'description' => '',
                    'help' => '',
                    'public' => true,
                    'tasks' => ['first' => [
                        'service' => '@test.task',
                        'options' => [],
                        'description' => '',
                        'help' => '',
                        'outputs' => [],
                        'errors' => [],
                        'error_outputs' => [],
                        'error_strategy' => null,
                        'log_level' => null,
                    ]],
                ],
            ],
            'stop'
        );

        return new CommandTester(new ExecuteProcessCommand($processManager, new EventDispatcher(), $registry));
    }

    public function testUnknownProcessSuggestsTheClosestOne(): void
    {
        $tester = $this->createTester($this->never());

        $this->expectExceptionMessage('Unknown process test.proces. Did you mean "test.process"? The processes are listed by the cleverage:process:list command.');
        $tester->execute(['processCodes' => ['test.proces']]);
    }

    public function testInvalidContextGivesTheExpectedFormat(): void
    {
        $tester = $this->createTester($this->never());

        $this->expectExceptionMessage('Invalid context foo: expected key:value, e.g. -c date:2024-01-01 (the value is parsed as YAML)');
        $tester->execute(['processCodes' => ['test.process'], '--context' => ['foo']]);
    }

    public function testUnknownOutputFormatGivesTheFormats(): void
    {
        $tester = $this->createTester($this->never());

        $this->expectExceptionMessage("Cannot handle data output with format 'jsn-stream': use 'dump' (STDOUT only) or 'json-stream'. Did you mean \"json-stream\"?");
        $tester->execute(['processCodes' => ['test.process'], '--output-format' => 'jsn-stream']);
    }
}
