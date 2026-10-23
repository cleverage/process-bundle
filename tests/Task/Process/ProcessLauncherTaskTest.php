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

namespace CleverAge\ProcessBundle\Tests\Task\Process;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Filesystem\TemporaryFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\SubprocessInstance;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\KernelInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessLauncherTask::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(ProcessLauncherTask::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SubprocessInstance::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TemporaryFile::class)]
class ProcessLauncherTaskTest extends TestCase
{
    private string $consoleDir;

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testDefaultOptionsAreResolved(): void
    {
        $this->createTask()->initialize($this->createState(['process' => 'child']));
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testEmptyProcessOptionsAreAccepted(): void
    {
        $this->createTask()->initialize($this->createState(['process' => 'child', 'process_options' => []]));
    }

    public function testNonEmptyProcessOptionsThrow(): void
    {
        $state = $this->createState(['process' => 'child', 'process_options' => ['foo' => 'bar']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deprecated option');
        $this->createTask()->initialize($state);
    }

    public function testUnknownProcessThrows(): void
    {
        $state = $this->createState(['process' => 'unknown']);

        $this->expectException(InvalidConfigurationException::class);
        $this->createTask()->initialize($state);
    }

    public function testPassingTheKernelIsDeprecated(): void
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn('/app');
        $kernel->method('getEnvironment')->willReturn('prod');
        $task = null;

        $deprecations = $this->collectDeprecations(function () use ($kernel, &$task): void {
            $task = new ProcessLauncherTask(new NullLogger(), $this->createRegistry(), $kernel);
        });

        $this->assertSame(
            ['Since cleverage/process-bundle 5.4: Passing a "Symfony\Component\HttpKernel\KernelInterface" to "CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask::__construct()" is deprecated, pass the path of the console and the environment instead. The argument will only accept a string in v6.0.'],
            $deprecations
        );
        $this->assertInstanceOf(ProcessLauncherTask::class, $task);
        $this->assertSame(['/app/bin/console', 'prod'], [
            (new \ReflectionProperty($task, 'consolePath'))->getValue($task),
            (new \ReflectionProperty($task, 'environment'))->getValue($task),
        ]);
    }

    public function testJsonBufferingOutputsThePathOfTheWrittenBuffer(): void
    {
        [$task, $state] = $this->launch('write');

        $this->assertTrue($task->next($state));
        $state->reset(false);
        $task->execute($state);

        $bufferPath = $state->getOutput();
        $this->assertIsString($bufferPath);
        $this->assertStringEqualsFile($bufferPath, "[\"a\"]\n");
        // Removed by the reader of the path, not by the task
        unlink($bufferPath);
    }

    public function testJsonBufferingOutputsNothingWhenTheSubprocessWroteNothing(): void
    {
        [$task, $state, $bufferPath] = $this->launch('nothing');

        $this->assertFalse($task->next($state));
        $state->reset(false);
        $task->flush($state);

        $this->assertTrue($state->isSkipped());
        $this->assertNull($state->getOutput());
        $this->assertFileDoesNotExist($bufferPath);
    }

    public function testJsonBufferIsRemovedWhenTheSubprocessFails(): void
    {
        [$task, $state, $bufferPath] = $this->launch('fail');

        try {
            $task->next($state);
            $this->fail('The failure of the sub-process must throw');
        } catch (\RuntimeException $e) {
            $this->assertSame('Sub-process has failed: General error', $e->getMessage());
        }
        $this->assertFileDoesNotExist($bufferPath);
    }

    public function testJsonBuffersOfTheStoppedSubprocessesAreRemovedWhenASubprocessFails(): void
    {
        [$task, $state, $runningBufferPath] = $this->launch('sleep', wait: false);
        [, , $failedBufferPath] = $this->launch('fail', $task, $state);

        try {
            $task->next($state);
            $this->fail('The failure of the sub-process must throw');
        } catch (\RuntimeException) {
        }
        $this->assertFileDoesNotExist($runningBufferPath);
        $this->assertFileDoesNotExist($failedBufferPath);
    }

    public function testJsonBufferOfATerminatedSubprocessIsKeptWhenAnotherSubprocessFails(): void
    {
        // The failed sub-process is handled first, the other one is already terminated but not handled yet
        [$task, $state, $failedBufferPath] = $this->launch('slowfail', wait: false);
        [, , $bufferPath] = $this->launch('write', $task, $state);
        $launchedProcesses = (new \ReflectionProperty($task, 'launchedProcesses'))->getValue($task);
        $this->assertIsArray($launchedProcesses);
        $failedSubprocess = reset($launchedProcesses);
        $this->assertInstanceOf(SubprocessInstance::class, $failedSubprocess);
        $failedSubprocess->getProcess()->wait();

        try {
            $state->reset(true);
            $task->execute($state);
            $this->fail('The failure of the sub-process must throw');
        } catch (\RuntimeException) {
        }
        $this->assertFileDoesNotExist($failedBufferPath);
        $this->assertFileExists($bufferPath);

        // With the "skip" error strategy, the process goes on: the result of the terminated sub-process is output
        $this->assertTrue($task->next($state));
        $state->reset(false);
        $task->execute($state);
        $this->assertSame($bufferPath, $state->getOutput());
        unlink($bufferPath);
    }

    protected function tearDown(): void
    {
        if (isset($this->consoleDir)) {
            unlink($this->consoleDir.'/console');
            rmdir($this->consoleDir);
        }
    }

    /**
     * Launches a sub-process with JSON buffering and waits for it. The fake console depends on the input: "write"
     * writes an array in the buffer, "fail" exits with an error ("slowfail" after 0.5 s), "sleep" keeps running,
     * otherwise it outputs nothing.
     *
     * @return array{ProcessLauncherTask, ProcessState, string} the task, its state and the path of the buffer
     */
    private function launch(string $input, ?ProcessLauncherTask $task = null, ?ProcessState $state = null, bool $wait = true): array
    {
        if (!isset($this->consoleDir)) {
            $this->consoleDir = sys_get_temp_dir().'/process_launcher_test_'.uniqid('', true);
            mkdir($this->consoleDir);
            file_put_contents($this->consoleDir.'/console', <<<'PHP'
                <?php
                $input = stream_get_contents(STDIN);
                if ('write' === $input) {
                    file_put_contents(substr($argv[array_key_last($argv) - 2], 9), "[\"a\"]\n");
                } elseif ('fail' === $input) {
                    exit(1);
                } elseif ('slowfail' === $input) {
                    usleep(500000);
                    exit(1);
                } elseif ('sleep' === $input) {
                    sleep(30);
                }
                PHP);
        }
        if (!$task instanceof ProcessLauncherTask || !$state instanceof ProcessState) {
            $task = new ProcessLauncherTask(new NullLogger(), $this->createRegistry(), $this->consoleDir.'/console');
            $state = $this->createState([
                'process' => 'child',
                'json_buffering' => true,
                'sleep_interval_after_launch' => 0,
                'sleep_on_finalize_interval' => 0,
            ]);
            $task->initialize($state);
        }
        $state->reset(true);
        $state->setInput($input);
        $task->execute($state);
        $this->assertTrue($state->isSkipped());

        $launchedProcesses = (new \ReflectionProperty($task, 'launchedProcesses'))->getValue($task);
        $this->assertIsArray($launchedProcesses);
        $subprocess = end($launchedProcesses);
        $this->assertInstanceOf(SubprocessInstance::class, $subprocess);
        if ($wait) {
            $subprocess->getProcess()->wait();
        }
        preg_match('#--output=([^\']+)#', $subprocess->getProcess()->getCommandLine(), $matches);
        $bufferPath = $matches[1] ?? '';
        $this->assertFileExists($bufferPath);

        return [$task, $state, $bufferPath];
    }

    private function createTask(): ProcessLauncherTask
    {
        return new ProcessLauncherTask(new NullLogger(), $this->createRegistry(), '/app/bin/console', 'test');
    }

    private function createRegistry(): ProcessConfigurationRegistry
    {
        $registry = $this->createStub(ProcessConfigurationRegistry::class);
        $registry->method('hasProcessConfiguration')->willReturnCallback(
            static fn (string $processCode): bool => 'child' === $processCode
        );

        return $registry;
    }

    /**
     * @param callable(): mixed $callback
     *
     * @return list<string>
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('launch', ProcessLauncherTask::class, $options));

        return $state;
    }
}
