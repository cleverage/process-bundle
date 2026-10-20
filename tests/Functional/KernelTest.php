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
use CleverAge\ProcessBundle\Tests\IgnoreDeprecationsTrait;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The bundle in an application (see TestKernel): the container compiles, the tasks and transformers are resolved with
 * their dependencies, a process runs.
 */
#[CoversNothing]
class KernelTest extends KernelTestCase
{
    use IgnoreDeprecationsTrait;

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
            $taskConfiguration = $processConfiguration->getTaskConfiguration($code);
            // Deprecated InputAggregatorTask, see InputAggregatorTaskTest::testTaskIsDeprecated()
            $task = 'input_aggregator' === $code
                ? self::ignoreDeprecations(static fn (): TaskInterface => $taskRegistry->getTask($taskConfiguration))
                : $taskRegistry->getTask($taskConfiguration);
            self::assertInstanceOf(TaskInterface::class, $task, $code);
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
