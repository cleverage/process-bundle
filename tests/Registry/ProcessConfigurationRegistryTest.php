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

namespace CleverAge\ProcessBundle\Tests\Registry;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

#[CoversClass(ProcessConfigurationRegistry::class)]
#[CoversClass(InvalidProcessConfigurationException::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
class ProcessConfigurationRegistryTest extends TestCase
{
    public function testTaskCodeNotResolvedAtCompileTimeThrows(): void
    {
        // e.g. a configuration added at runtime: the "task" codes are only resolved when the container is compiled
        $registry = new ProcessConfigurationRegistry([
            'test.process' => [
                'options' => [],
                'entry_point' => null,
                'end_point' => null,
                'description' => '',
                'help' => '',
                'public' => true,
                'tasks' => [
                    'my_task' => ['service' => null, 'task' => 'transformer', 'errors' => [], 'error_outputs' => []],
                ],
            ],
        ], 'stop');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The task my_task of the process test.process has no service: a "task" code can only be used in the configurations known when the container is compiled');

        $registry->getProcessConfiguration('test.process');
    }

    public function testResetForgetsTheResolvedConfigurations(): void
    {
        $registry = new ProcessConfigurationRegistry([
            'test.process' => [
                'options' => [],
                'entry_point' => null,
                'end_point' => null,
                'description' => '',
                'help' => '',
                'public' => true,
                'tasks' => [
                    'my_task' => [
                        'service' => '@app.task',
                        'options' => [],
                        'description' => '',
                        'help' => '',
                        'outputs' => [],
                        'errors' => [],
                        'error_outputs' => [],
                        'error_strategy' => null,
                        'log_level' => null,
                    ],
                ],
            ],
        ], 'stop');
        $configuration = $registry->getProcessConfiguration('test.process');
        self::assertSame($configuration, $registry->getProcessConfiguration('test.process'));

        $registry->reset();

        // Resolved again: the task instances and states of the previous executions are released
        $resolvedAgain = $registry->getProcessConfiguration('test.process');
        self::assertNotSame($configuration, $resolvedAgain);
        self::assertSame('test.process', $resolvedAgain->getCode());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, array<string, mixed>>, string}>
     */
    public static function provideUnknownTaskReferences(): iterable
    {
        yield 'outputs' => [
            [],
            ['read' => ['outputs' => ['missing']], 'write' => []],
            "Unknown task 'missing' in the 'outputs' of the task 'read' (from process: test.process)",
        ];
        yield 'error_outputs' => [
            [],
            ['read' => ['outputs' => ['write'], 'error_outputs' => ['missing']], 'write' => []],
            "Unknown task 'missing' in the 'error_outputs' of the task 'read' (from process: test.process)",
        ];
        yield 'deprecated errors' => [
            [],
            ['read' => ['errors' => ['missing']], 'write' => []],
            "Unknown task 'missing' in the 'errors' of the task 'read' (from process: test.process)",
        ];
        yield 'entry_point' => [
            ['entry_point' => 'missing'],
            ['read' => ['outputs' => ['write']], 'write' => []],
            "Unknown task 'missing' in the 'entry_point' (from process: test.process)",
        ];
        yield 'end_point' => [
            ['entry_point' => 'read', 'end_point' => 'missing'],
            ['read' => ['outputs' => ['write']], 'write' => []],
            "Unknown task 'missing' in the 'end_point' (from process: test.process)",
        ];
    }

    /**
     * @param array<string, mixed>                $processConfiguration
     * @param array<string, array<string, mixed>> $tasks
     */
    #[DataProvider('provideUnknownTaskReferences')]
    public function testUnknownTaskReferenceNamesTheProcessAndTheTask(array $processConfiguration, array $tasks, string $message): void
    {
        $registry = new ProcessConfigurationRegistry(['test.process' => $this->rawProcess($tasks, $processConfiguration)], 'stop');

        $this->expectException(InvalidProcessConfigurationException::class);
        $this->expectExceptionMessage($message);

        $registry->getProcessConfiguration('test.process');
    }

    public function testListingLeavesOutAndLogsTheInvalidProcesses(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{mixed, string, array<mixed>}> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = [$level, (string) $message, $context];
            }
        };
        $registry = new ProcessConfigurationRegistry([
            'test.valid' => $this->rawProcess(['read' => ['outputs' => ['write']], 'write' => []]),
            'test.unknown_output' => $this->rawProcess(['read' => ['outputs' => ['missing']]]),
            'test.no_service' => $this->rawProcess(['read' => ['service' => null]]),
            'test.other' => $this->rawProcess(['read' => []]),
        ], 'stop', [], $logger);

        $configurations = $registry->getProcessConfigurations();

        self::assertSame(['test.valid', 'test.other'], array_keys($configurations));
        self::assertSame(['test.valid', 'test.other'], array_keys($registry->getProcessConfigurations()));
        self::assertCount(4, $logger->records);
        [$level, $message, $context] = $logger->records[0];
        self::assertSame('error', $level);
        self::assertSame("The process test.unknown_output is left out of the process list, its configuration is not valid: Unknown task 'missing' in the 'outputs' of the task 'read' (from process: test.unknown_output)", $message);
        self::assertSame('test.unknown_output', $context['process_code']);
        self::assertInstanceOf(InvalidProcessConfigurationException::class, $context['exception']);
        self::assertStringStartsWith('The process test.no_service is left out of the process list', $logger->records[1][1]);

        // Requested by its code (e.g. to be executed), the invalid process still throws
        $this->expectException(InvalidProcessConfigurationException::class);
        $this->expectExceptionMessage("Unknown task 'missing' in the 'outputs' of the task 'read' (from process: test.unknown_output)");
        $registry->getProcessConfiguration('test.unknown_output');
    }

    public function testListingWithoutLogger(): void
    {
        $registry = new ProcessConfigurationRegistry([
            'test.invalid' => $this->rawProcess(['read' => []], ['entry_point' => 'missing']),
            'test.valid' => $this->rawProcess(['read' => []]),
        ], 'stop');

        self::assertSame(['test.valid'], array_keys($registry->getProcessConfigurations()));
    }

    public function testListingKeepsTheConfigurationsResolvedBefore(): void
    {
        $registry = new ProcessConfigurationRegistry([
            'test.a' => $this->rawProcess(['read' => []]),
            'test.b' => $this->rawProcess(['read' => []]),
        ], 'stop');
        $configuration = $registry->getProcessConfiguration('test.b');

        $configurations = $registry->getProcessConfigurations();

        self::assertSame(['test.a', 'test.b'], array_keys($configurations));
        self::assertSame($configuration, $configurations['test.b']);
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     * @param array<string, mixed>                $processConfiguration
     *
     * @return array<string, mixed>
     */
    private function rawProcess(array $tasks, array $processConfiguration = []): array
    {
        return $processConfiguration + [
            'options' => [],
            'entry_point' => null,
            'end_point' => null,
            'description' => '',
            'help' => '',
            'public' => true,
            'tasks' => array_map(static fn (array $task): array => $task + [
                'service' => '@app.task',
                'options' => [],
                'description' => '',
                'help' => '',
                'outputs' => [],
                'errors' => [],
                'error_outputs' => [],
                'error_strategy' => null,
                'log_level' => null,
            ], $tasks),
        ];
    }
}
