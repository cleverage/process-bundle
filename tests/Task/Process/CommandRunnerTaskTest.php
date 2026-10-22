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
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Process\CommandRunnerTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[\PHPUnit\Framework\Attributes\CoversClass(CommandRunnerTask::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(CommandRunnerTask::class, 'execute')]
#[\PHPUnit\Framework\Attributes\CoversMethod(CommandRunnerTask::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class CommandRunnerTaskTest extends TestCase
{
    public function testExecuteWithArrayCommandline(): void
    {
        $state = $this->createState(['commandline' => ['echo', 'hello']]);

        $this->createTask()->execute($state);

        $this->assertSame("hello\n", $state->getOutput());
    }

    public function testExecuteWithStringCommandline(): void
    {
        $state = $this->createState(['commandline' => 'echo "hello world" | tr a-z A-Z']);

        $this->createTask()->execute($state);

        $this->assertSame("HELLO WORLD\n", $state->getOutput());
    }

    public function testInputIsPassedToCommand(): void
    {
        $state = $this->createState(['commandline' => ['cat']]);
        $state->setInput('from input');

        $this->createTask()->execute($state);

        $this->assertSame('from input', $state->getOutput());
    }

    public function testCwdAndEnvOptions(): void
    {
        $state = $this->createState([
            'commandline' => 'echo "$FOO" && pwd',
            'cwd' => sys_get_temp_dir(),
            'env' => ['FOO' => 'bar'],
        ]);

        $this->createTask()->execute($state);

        $this->assertSame('bar'.\PHP_EOL.realpath(sys_get_temp_dir()).\PHP_EOL, $state->getOutput());
    }

    public function testProjectDirIsTheDefaultCwd(): void
    {
        $state = $this->createState(['commandline' => 'pwd']);

        $this->createTask()->execute($state);

        $this->assertSame(realpath(sys_get_temp_dir()).\PHP_EOL, $state->getOutput());
    }

    public function testPassingTheKernelIsDeprecated(): void
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn(sys_get_temp_dir());
        $task = null;

        $deprecations = $this->collectDeprecations(static function () use ($kernel, &$task): void {
            $task = new CommandRunnerTask($kernel);
        });

        $this->assertSame(
            ['Since cleverage/process-bundle 5.4: Passing a "Symfony\Component\HttpKernel\KernelInterface" to "CleverAge\ProcessBundle\Task\Process\CommandRunnerTask::__construct()" is deprecated, pass the project directory instead. The argument will only accept a string in v6.0.'],
            $deprecations
        );
        $this->assertInstanceOf(CommandRunnerTask::class, $task);
        $state = $this->createState(['commandline' => 'pwd']);
        $task->execute($state);
        $this->assertSame(realpath(sys_get_temp_dir()).\PHP_EOL, $state->getOutput());
    }

    public function testProcessOptionsArePassedToProcess(): void
    {
        $state = $this->createState([
            'commandline' => ['echo', 'hello'],
            'options' => ['create_new_console' => false],
        ]);

        $this->createTask()->execute($state);

        $this->assertSame("hello\n", $state->getOutput());
    }

    public function testInvalidProcessOptionsType(): void
    {
        $state = $this->createState([
            'commandline' => ['echo', 'hello'],
            'options' => 'invalid',
        ]);

        $this->expectException(InvalidOptionsException::class);
        $this->createTask()->execute($state);
    }

    public function testFailingCommandThrows(): void
    {
        $state = $this->createState(['commandline' => ['false']]);

        $this->expectException(ProcessFailedException::class);
        $this->createTask()->execute($state);
    }

    private function createTask(): CommandRunnerTask
    {
        return new CommandRunnerTask(sys_get_temp_dir());
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
        $state->setTaskConfiguration(new TaskConfiguration('command', CommandRunnerTask::class, $options));

        return $state;
    }
}
