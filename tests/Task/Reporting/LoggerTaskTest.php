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

namespace CleverAge\ProcessBundle\Tests\Task\Reporting;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Reporting\LoggerTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(LoggerTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class LoggerTaskTest extends TestCase
{
    /** @var list<array{level: mixed, message: string, context: array<mixed>}> */
    private array $records = [];

    public function testDefaultOptionsLogTheInputAtDebugLevel(): void
    {
        $state = $this->execute([], ['id' => 123]);

        self::assertSame(
            [['level' => LogLevel::DEBUG, 'message' => 'Log state input', 'context' => ['input' => ['id' => 123]]]],
            $this->records
        );
        self::assertSame(['id' => 123], $state->getOutput());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function inputProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'array' => [['foo' => 'bar']];
        yield 'object' => [new \ArrayObject(['foo' => 'bar'])];
        yield 'null' => [null];
    }

    #[DataProvider('inputProvider')]
    public function testInputIsForwardedUnchanged(mixed $input): void
    {
        $state = $this->execute([], $input);

        self::assertSame($input, $state->getOutput());
        self::assertFalse($state->isSkipped());
    }

    public function testLevelAndMessageAreUsed(): void
    {
        $this->execute(['level' => LogLevel::WARNING, 'message' => 'DEMO LOGGER'], 'foo');

        self::assertCount(1, $this->records);
        self::assertSame(LogLevel::WARNING, $this->records[0]['level']);
        self::assertSame('DEMO LOGGER', $this->records[0]['message']);
    }

    public function testContextPathsAreReadOnTheState(): void
    {
        $this->execute(['context' => ['input', 'context']], 'foo', ['file' => 'import.csv']);

        self::assertSame(['input' => 'foo', 'context' => ['file' => 'import.csv']], $this->records[0]['context']);
    }

    public function testEmptyContextLogsNoValue(): void
    {
        $this->execute(['context' => []], 'foo');

        self::assertSame([], $this->records[0]['context']);
    }

    public function testReferenceIsAddedToTheLogContext(): void
    {
        $this->execute(['reference' => 'ref-1'], 'foo');

        self::assertSame(['input' => 'foo', 'reference' => 'ref-1'], $this->records[0]['context']);
    }

    public function testReferenceIsContextualized(): void
    {
        $this->execute(['context' => [], 'reference' => '{{ file }}'], 'foo', ['file' => 'import.csv']);

        self::assertSame(['reference' => 'import.csv'], $this->records[0]['context']);
    }

    public function testEmptyReferenceIsNotAddedToTheLogContext(): void
    {
        $this->execute(['reference' => ''], 'foo');

        self::assertSame(['input' => 'foo'], $this->records[0]['context']);
    }

    public function testUnreadableContextPathThrows(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->execute(['context' => ['unknownProperty']], 'foo');
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'level not a string' => [['level' => 100], InvalidOptionsException::class];
        yield 'message not a string' => [['message' => ['foo']], InvalidOptionsException::class];
        yield 'context not an array' => [['context' => 'input'], InvalidOptionsException::class];
        yield 'reference not a string' => [['reference' => 42], InvalidOptionsException::class];
        yield 'unknown option' => [['unknown' => true], UndefinedOptionsException::class];
    }

    /**
     * @param array<string, mixed>     $options
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrowAtInitialization(array $options, string $exception): void
    {
        $this->expectException($exception);
        $this->createTask()->initialize($this->createState($options, 'foo'));
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $context
     */
    private function execute(array $options, mixed $input, array $context = []): ProcessState
    {
        $state = $this->createState($options, $input, $context);
        $task = $this->createTask();
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    private function createTask(): LoggerTask
    {
        $logger = new class(function (array $record): void {
            $this->records[] = $record;
        }) extends AbstractLogger {
            public function __construct(private readonly \Closure $onLog)
            {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ($this->onLog)(['level' => $level, 'message' => (string) $message, 'context' => $context]);
            }
        };

        return new LoggerTask($logger, PropertyAccess::createPropertyAccessor());
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
        $state->setTaskConfiguration(new TaskConfiguration('log', LoggerTask::class, $options));
        $state->reset(false);
        $state->setInput($input);

        return $state;
    }
}
