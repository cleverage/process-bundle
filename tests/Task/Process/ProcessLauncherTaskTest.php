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
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
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
class ProcessLauncherTaskTest extends TestCase
{
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
