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

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\RowAggregatorTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(RowAggregatorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class RowAggregatorTaskTest extends TestCase
{
    private const OPTIONS = [
        'aggregate_by' => 'order_id',
        'aggregate_columns' => ['product', 'qty'],
        'aggregation_key' => 'lines',
    ];

    public function testRowsAreGroupedByColumn(): void
    {
        $state = $this->createState(self::OPTIONS);
        $task = new RowAggregatorTask(new NullLogger());
        $task->initialize($state);

        foreach ([
            ['order_id' => 1, 'customer' => 'X', 'product' => 'A', 'qty' => 2],
            ['order_id' => 2, 'customer' => 'Y', 'product' => 'C', 'qty' => 1],
            ['order_id' => 1, 'customer' => 'Z', 'product' => 'B', 'qty' => 3],
        ] as $input) {
            $state->setInput($input);
            $task->execute($state);
        }
        $task->proceed($state);

        self::assertSame([
            // The non-aggregated columns come from the first row of the group
            ['order_id' => 1, 'customer' => 'X', 'lines' => [['product' => 'A', 'qty' => 2], ['product' => 'B', 'qty' => 3]]],
            ['order_id' => 2, 'customer' => 'Y', 'lines' => [['product' => 'C', 'qty' => 1]]],
        ], $state->getOutput());
    }

    public function testProceedWithoutInputOutputsAnEmptyList(): void
    {
        $state = $this->createState(self::OPTIONS);
        $task = new RowAggregatorTask(new NullLogger());
        $task->initialize($state);

        $task->proceed($state);

        self::assertSame([], $state->getOutput());
    }

    public function testMissingAggregateByColumnThrows(): void
    {
        $state = $this->createState(self::OPTIONS);
        $task = new RowAggregatorTask(new NullLogger());
        $task->initialize($state);
        $state->setInput(['customer' => 'X', 'product' => 'A', 'qty' => 2]);

        $this->expectException(InvalidProcessConfigurationException::class);
        $this->expectExceptionMessage("Array aggregator exception: missing column 'order_id'");

        $task->execute($state);
    }

    public function testMissingAggregateColumnThrows(): void
    {
        $state = $this->createState(self::OPTIONS);
        $task = new RowAggregatorTask(new NullLogger());
        $task->initialize($state);
        $state->setInput(['order_id' => 1, 'product' => 'A']);

        $this->expectException(InvalidProcessConfigurationException::class);
        $this->expectExceptionMessage('Array aggregator exception: missing column qty');

        $task->execute($state);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        foreach (array_keys(self::OPTIONS) as $option) {
            $options = self::OPTIONS;
            unset($options[$option]);
            yield "missing {$option}" => [$options, MissingOptionsException::class];
        }
        yield 'aggregate_by not a string' => [['aggregate_by' => ['order_id']] + self::OPTIONS, InvalidOptionsException::class];
        yield 'aggregate_columns not an array' => [['aggregate_columns' => 'product'] + self::OPTIONS, InvalidOptionsException::class];
        yield 'aggregation_key not a string' => [['aggregation_key' => 1] + self::OPTIONS, InvalidOptionsException::class];
    }

    /**
     * @param array<string, mixed>     $options
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsAreRejected(array $options, string $exception): void
    {
        $task = new RowAggregatorTask(new NullLogger());

        $this->expectException($exception);

        $task->initialize($this->createState($options));
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
        $state->setTaskConfiguration(new TaskConfiguration('aggregate', RowAggregatorTask::class, $options));

        return $state;
    }
}
