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
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\FilterTask;
use CleverAge\ProcessBundle\Transformer\ConditionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(FilterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesTrait(ConditionTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FilterTaskTest extends TestCase
{
    /**
     * Inputs ported from the former kernel-based FilterTaskTest.
     *
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return [
            ['key1' => 'value1', 'key2' => 'value2', 'key3' => ['something']],
            ['key1' => 'value1b', 'key2' => 'value2b', 'key3' => ['something']],
            ['key1' => 'value1c', 'key2' => 'value2c', 'key3' => []],
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<int>}>
     */
    public static function filterProvider(): iterable
    {
        yield 'no condition' => [[], [0, 1, 2]];
        yield 'match' => [['match' => ['[key1]' => 'value1']], [0]];
        yield 'match several keys' => [['match' => ['[key1]' => 'value1b', '[key2]' => 'value2b']], [1]];
        yield 'match several keys, one failing' => [['match' => ['[key1]' => 'value1b', '[key2]' => 'value2']], []];
        yield 'not_match' => [['not_match' => ['[key1]' => 'value1']], [1, 2]];
        yield 'empty' => [['empty' => ['[key3]' => null]], [2]];
        yield 'not_empty' => [['not_empty' => ['[key3]' => null]], [0, 1]];
        yield 'match_regexp' => [['match_regexp' => ['[key1]' => '/^value1[bc]$/']], [1, 2]];
        yield 'not_match_regexp' => [['not_match_regexp' => ['[key1]' => '/^value1[bc]$/']], [0]];
        yield 'missing key is null on match' => [['match' => ['[missing]' => null]], [0, 1, 2]];
        yield 'missing key is empty' => [['empty' => ['[missing]' => null]], [0, 1, 2]];
        yield 'missing key is not not_empty' => [['not_empty' => ['[missing]' => null]], []];
        yield 'combined conditions' => [
            ['not_empty' => ['[key3]' => null], 'not_match' => ['[key1]' => 'value1']],
            [1],
        ];
    }

    /**
     * @param list<int> $expectedKept
     */
    #[DataProvider('filterProvider')]
    public function testInputsAreFiltered(array $options, array $expectedKept): void
    {
        $items = $this->items();
        $kept = [];
        foreach ($items as $index => $item) {
            $state = $this->execute($options, $item);
            if ($state->isSkipped()) {
                self::assertSame($item, $state->getErrorOutput());
                self::assertNull($state->getOutput());
            } else {
                self::assertSame($item, $state->getOutput());
                self::assertFalse($state->hasErrorOutput());
                $kept[] = $index;
            }
        }

        self::assertSame($expectedKept, $kept);
    }

    public function testMatchIsStrict(): void
    {
        self::assertTrue($this->execute(['match' => ['[id]' => 1]], ['id' => '1'])->isSkipped());
        self::assertFalse($this->execute(['match' => ['[id]' => 1]], ['id' => 1])->isSkipped());
        self::assertFalse($this->execute(['not_match' => ['[id]' => 1]], ['id' => '1'])->isSkipped());
    }

    public function testObjectPropertiesAreRead(): void
    {
        $input = (object) ['status' => 'active', 'sku' => 'ABC'];

        $state = $this->execute(['match' => ['status' => 'active'], 'not_empty' => ['sku' => null]], $input);
        self::assertFalse($state->isSkipped());
        self::assertSame($input, $state->getOutput());

        $state = $this->execute(['match' => ['status' => 'inactive']], $input);
        self::assertTrue($state->isSkipped());
        self::assertSame($input, $state->getErrorOutput());
    }

    public function testUnreadableObjectPropertyIsNull(): void
    {
        $input = new \stdClass();

        self::assertFalse($this->execute(['match' => ['missing' => null]], $input)->isSkipped());
        self::assertFalse($this->execute(['empty' => ['missing' => null]], $input)->isSkipped());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, mixed, bool}>
     */
    public static function scalarProvider(): iterable
    {
        yield 'empty path matches whole value' => [['match' => ['' => 'foo']], 'foo', false];
        yield 'empty path does not match whole value' => [['match' => ['' => 'bar']], 'foo', true];
        yield 'empty path regexp' => [['match_regexp' => ['' => '/^fo+$/']], 'foo', false];
        yield 'empty path empty' => [['empty' => ['' => null]], '', false];
        yield 'empty path not_empty' => [['not_empty' => ['' => null]], 0, true];
        yield 'other path on scalar is null' => [['match' => ['[key]' => null]], 'foo', false];
        yield 'other path on scalar is empty' => [['not_empty' => ['[key]' => null]], 'foo', true];
    }

    #[DataProvider('scalarProvider')]
    public function testScalarInput(array $options, mixed $input, bool $expectedSkipped): void
    {
        self::assertSame($expectedSkipped, $this->execute($options, $input)->isSkipped());
    }

    public function testNonArrayConditionIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->execute(['match' => 'foo'], []);
    }

    public function testUnknownConditionIsRejected(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->execute(['equals' => []], []);
    }

    private function execute(array $options, mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('filter', FilterTask::class, $options));
        $state->setInput($input);
        $state->reset(false);

        $task = new FilterTask();
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }
}
