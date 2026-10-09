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
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessExecutorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfigurationRegistry::class)]
class ProcessExecutorTaskTest extends TestCase
{
    public function testInputIsPassedToTheSubProcessAndItsOutputIsForwarded(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::once())
            ->method('execute')
            ->with('child', ['id' => 1], [])
            ->willReturn([1, 2, 3, 4]);

        $state = $this->execute($processManager, ['process' => 'child'], ['id' => 1]);

        self::assertSame([1, 2, 3, 4], $state->getOutput());
        self::assertFalse($state->isSkipped());
    }

    public function testEachInputExecutesTheSubProcess(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::exactly(2))
            ->method('execute')
            ->willReturnCallback(static fn (string $code, mixed $input): string => $code.':'.$input);

        $state = $this->createState(['process' => 'child'], 'a');
        $task = $this->createTask($processManager);
        $task->initialize($state);
        $outputs = [];
        foreach (['a', 'b'] as $input) {
            $state->setInput($input);
            $task->execute($state);
            $outputs[] = $state->getOutput();
        }

        self::assertSame(['child:a', 'child:b'], $outputs);
    }

    public function testNullSubProcessOutputIsForwarded(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::once())->method('execute')->willReturn(null);

        $state = $this->execute($processManager, ['process' => 'child'], 'foo');

        self::assertNull($state->getOutput());
    }

    public function testContextOptionIsPassedToTheSubProcess(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::once())
            ->method('execute')
            ->with('child', 'foo', ['source' => 'import.csv', 'mode' => 'full'])
            ->willReturn('done');

        $state = $this->execute(
            $processManager,
            ['process' => 'child', 'context' => ['source' => '{{ source }}', 'mode' => 'full']],
            'foo',
            ['source' => 'import.csv', 'other' => 'not forwarded'],
        );

        self::assertSame('done', $state->getOutput());
    }

    public function testProcessCodeIsContextualized(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::once())->method('execute')->with('child', 'foo', [])->willReturn('done');

        $state = $this->execute($processManager, ['process' => '{{ sub }}'], 'foo', ['sub' => 'child']);

        self::assertSame('done', $state->getOutput());
    }

    public function testSubProcessExceptionIsPropagated(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::once())->method('execute')->willThrowException(new \RuntimeException('Sub-process failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sub-process failed');
        $this->execute($processManager, ['process' => 'child'], 'foo');
    }

    public function testUnknownProcessThrowsAtInitialization(): void
    {
        $processManager = $this->createMock(ProcessManager::class);
        $processManager->expects(self::never())->method('execute');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown process unknown');
        $this->createTask($processManager)->initialize($this->createState(['process' => 'unknown'], 'foo'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'missing process' => [[], MissingOptionsException::class];
        yield 'process not a string' => [['process' => 42], InvalidOptionsException::class];
        yield 'context not an array' => [['process' => 'child', 'context' => 'foo'], InvalidOptionsException::class];
        yield 'unknown option' => [['process' => 'child', 'unknown' => true], UndefinedOptionsException::class];
    }

    /**
     * @param array<string, mixed>     $options
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrowAtInitialization(array $options, string $exception): void
    {
        $this->expectException($exception);
        $this->createTask($this->createStub(ProcessManager::class))->initialize($this->createState($options, 'foo'));
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $context
     */
    private function execute(ProcessManager $processManager, array $options, mixed $input, array $context = []): ProcessState
    {
        $state = $this->createState($options, $input, $context);
        $task = $this->createTask($processManager);
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    private function createTask(ProcessManager $processManager): ProcessExecutorTask
    {
        return new ProcessExecutorTask(
            $processManager,
            new ProcessConfigurationRegistry(['child' => []], 'stop'),
            new NullLogger(),
        );
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $context
     */
    private function createState(array $options, mixed $input, array $context = []): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext($context);
        $state->setTaskConfiguration(new TaskConfiguration('executor', ProcessExecutorTask::class, $options));
        $state->reset(false);
        $state->setInput($input);

        return $state;
    }
}
