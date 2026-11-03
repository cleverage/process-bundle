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

namespace CleverAge\ProcessBundle\Tests\Configuration;

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\CircularProcessException;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The dependency groups of a process (getDependencyGroups(), getMainTaskGroup()): the process manager resolves the
 * tasks of the main group in this order.
 */
#[CoversClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(DeprecatedCodeAliases::class)]
#[UsesClass(CircularProcessException::class)]
class ProcessConfigurationTest extends TestCase
{
    /**
     * @return iterable<string, array{int}>
     */
    public static function provideSeeds(): iterable
    {
        foreach (range(1, 30) as $seed) {
            yield 'graph '.$seed => [$seed];
        }
    }

    /**
     * Random acyclic graphs (fixed seeds): each group contains the connected tasks once, each task after its
     * ancestors.
     */
    #[DataProvider('provideSeeds')]
    public function testDependencyGroupsAreSorted(int $seed): void
    {
        mt_srand($seed);
        $count = mt_rand(2, 14);
        // Tasks declared in a random order, edges from a lower to a higher rank: no cycle
        $ranks = range(0, $count - 1);
        shuffle($ranks);
        $tasks = [];
        foreach ($ranks as $rank) {
            $tasks['t'.$rank] = ['outputs' => [], 'error_outputs' => []];
        }
        for ($from = 0; $from < $count; ++$from) {
            for ($to = $from + 1; $to < $count; ++$to) {
                $random = mt_rand(0, 9);
                if ($random < 2) {
                    $tasks['t'.$from]['outputs'][] = 't'.$to;
                } elseif (2 === $random) {
                    $tasks['t'.$from]['error_outputs'][] = 't'.$to;
                }
            }
        }
        $processConfiguration = $this->createProcessConfiguration($tasks);

        $groups = $processConfiguration->getDependencyGroups();

        $grouped = array_merge(...$groups);
        sort($grouped);
        $codes = array_keys($tasks);
        sort($codes);
        self::assertSame($codes, $grouped, 'Each task in a single group');
        foreach ($groups as $group) {
            $positions = array_flip($group);
            foreach ($group as $code) {
                $task = $processConfiguration->getTaskConfiguration($code);
                foreach ($group as $otherCode) {
                    if ($task->hasAncestor($processConfiguration->getTaskConfiguration($otherCode))) {
                        self::assertLessThan($positions[$code], $positions[$otherCode], "{$otherCode} before {$code}");
                    }
                }
                // Connected tasks are in the same group
                foreach ([...$task->getNextTasksConfigurations(), ...$task->getErrorTasksConfigurations()] as $next) {
                    self::assertArrayHasKey($next->getCode(), $positions);
                }
            }
        }
    }

    public function testChainDeclaredInReverseOrder(): void
    {
        $processConfiguration = $this->createProcessConfiguration([
            'e' => [],
            'd' => ['outputs' => ['e']],
            'c' => ['outputs' => ['d']],
            'b' => ['outputs' => ['c']],
            'a' => ['outputs' => ['b']],
        ]);

        self::assertSame([['a', 'b', 'c', 'd', 'e']], $processConfiguration->getDependencyGroups());
    }

    /**
     * The main group is the one of the entry point, else of the end point, else of the first task.
     */
    public function testMainTaskGroup(): void
    {
        $tasks = [
            'a1' => ['outputs' => ['a2']],
            'a2' => [],
            'b1' => ['outputs' => ['b2']],
            'b2' => [],
            'c1' => [],
        ];

        self::assertSame(['a1', 'a2'], $this->createProcessConfiguration($tasks)->getMainTaskGroup());
        self::assertSame('a1', $this->createProcessConfiguration($tasks)->getMainTask()?->getCode());
        self::assertSame(['b1', 'b2'], $this->createProcessConfiguration($tasks, ['end_point' => 'b2'])->getMainTaskGroup());
        self::assertSame(['c1'], $this->createProcessConfiguration($tasks, ['entry_point' => 'c1', 'end_point' => 'b2'])->getMainTaskGroup());
        self::assertSame(
            [['a1', 'a2'], ['b1', 'b2'], ['c1']],
            $this->createProcessConfiguration($tasks)->getDependencyGroups()
        );
    }

    public function testWithoutTask(): void
    {
        $processConfiguration = new ProcessConfiguration('test.process', []);

        self::assertNull($processConfiguration->getMainTask());
        self::assertSame([], $processConfiguration->getMainTaskGroup());
        self::assertSame([], $processConfiguration->getDependencyGroups());
        self::assertTrue($processConfiguration->isPublic(), 'Public by default');
    }

    /**
     * With a cycle, the tasks are not sorted (the cycle is reported when the process is executed).
     */
    public function testCycleIsNotSorted(): void
    {
        $processConfiguration = $this->createProcessConfiguration([
            'c' => ['outputs' => ['a']],
            'b' => ['outputs' => ['c']],
            'a' => ['outputs' => ['b']],
            'd' => ['outputs' => ['a']],
        ]);

        self::assertSame([['c', 'b', 'a', 'd']], $processConfiguration->getDependencyGroups());

        $this->expectException(CircularProcessException::class);
        $processConfiguration->checkCircularDependencies();
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     * @param array<string, mixed>                $processConfiguration
     */
    private function createProcessConfiguration(array $tasks, array $processConfiguration = []): ProcessConfiguration
    {
        $registry = new ProcessConfigurationRegistry([
            'test.process' => $processConfiguration + [
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
            ],
        ], 'stop');

        return $registry->getProcessConfiguration('test.process');
    }
}
