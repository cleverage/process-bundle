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
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn(sys_get_temp_dir());

        return new CommandRunnerTask($kernel);
    }

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
