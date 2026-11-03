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

use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Tests\App\TestKernel;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The bundle in an application (see TestKernel): the container compiles, the tasks and transformers are resolved with
 * their dependencies, a process runs.
 */
#[CoversNothing]
class KernelTest extends KernelTestCase
{
    private mixed $exceptionHandler;

    protected function setUp(): void
    {
        $this->exceptionHandler = $this->getExceptionHandler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // The ErrorHandler registered when the kernel boots is not removed by the first Symfony 6.4 versions
        if ($this->getExceptionHandler() !== $this->exceptionHandler) {
            restore_exception_handler();
        }
    }

    public function testEveryTaskIsResolvedByItsCode(): void
    {
        $processConfigurationRegistry = self::getContainer()->get('test.process_configuration_registry');
        self::assertInstanceOf(ProcessConfigurationRegistry::class, $processConfigurationRegistry);
        $taskRegistry = self::getContainer()->get('test.task_registry');
        self::assertInstanceOf(TaskRegistry::class, $taskRegistry);

        $processConfiguration = $processConfigurationRegistry->getProcessConfiguration('test.every_task');
        $codes = TestKernel::getTaskCodes();
        self::assertNotEmpty($codes);
        foreach ($codes as $code) {
            self::assertInstanceOf(TaskInterface::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration($code)), $code);
        }
    }

    public function testEveryTransformerIsResolved(): void
    {
        $transformerRegistry = self::getContainer()->get('test.transformer_registry');
        self::assertInstanceOf(TransformerRegistry::class, $transformerRegistry);

        $transformers = $transformerRegistry->getTransformers();
        self::assertNotEmpty($transformers);
        self::assertContainsOnlyInstancesOf(TransformerInterface::class, $transformers);
        self::assertInstanceOf(TransformerInterface::class, $transformerRegistry->getTransformer('sprintf'));
    }

    public function testProcessIsExecuted(): void
    {
        $processManager = self::getContainer()->get('test.process_manager');
        self::assertInstanceOf(ProcessManager::class, $processManager);

        self::assertSame('Hello world', $processManager->execute('test.run'));
    }

    /**
     * @return iterable<string, array{string, list<array<string, mixed>>}>
     */
    public static function provideInputAggregatorProcesses(): iterable
    {
        yield 'filtered items' => ['test.input_aggregator', [['a' => 'a1', 'b' => 'b1'], ['a' => 'a3', 'b' => 'b3'], ['a' => 'a5', 'b' => 'b5']]];
        yield 'error with the skip strategy' => ['test.input_aggregator_error', [['a' => 1, 'b' => 'b1'], ['a' => 3, 'b' => 'b3']]];
    }

    /**
     * @param list<array<string, mixed>> $expected
     */
    #[DataProvider('provideInputAggregatorProcesses')]
    public function testInputAggregatorDropsTheItemsSkippedByABranch(string $processCode, array $expected): void
    {
        $processManager = self::getContainer()->get('test.process_manager');
        self::assertInstanceOf(ProcessManager::class, $processManager);

        self::assertSame($expected, $processManager->execute($processCode));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function provideFlushableEndPoints(): iterable
    {
        yield 'last batch outputted by the flush' => ['simple_batch_flushed', [3]];
        yield 'last batch outputted by the execution' => ['simple_batch_executed', [1, 2, 3]];
        yield 'iterable batch' => ['iterable_batch', 3];
        yield 'counter' => ['counter', 3];
    }

    /**
     * The output of a flushable end point is kept by its next flushes with nothing to output: the process returned null.
     */
    #[DataProvider('provideFlushableEndPoints')]
    public function testFlushableEndPoint(string $name, mixed $expected): void
    {
        $processManager = self::getContainer()->get('test.process_manager');
        self::assertInstanceOf(ProcessManager::class, $processManager);

        self::assertSame($expected, $processManager->execute('test.flushable_end_point.'.$name));
    }

    private function getExceptionHandler(): mixed
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
