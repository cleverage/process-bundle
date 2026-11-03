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

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskConfiguration::class)]
class TaskConfigurationTest extends TestCase
{
    public function testGetErrorsIsDeprecated(): void
    {
        $taskConfiguration = new TaskConfiguration('task', 'service', [], errorOutputs: ['error']);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $errors = $taskConfiguration->getErrors();
        } finally {
            restore_error_handler();
        }

        self::assertSame(['error'], $errors);
        self::assertSame(
            ['Since cleverage/process-bundle 2.0.4: The "CleverAge\ProcessBundle\Configuration\TaskConfiguration::getErrors()" method is deprecated, use "getErrorOutputs()" instead.'],
            $deprecations
        );
    }

    public function testHasAncestorAndHasDescendant(): void
    {
        // a -> b -> d, a -> c -> d, d -(error)-> e, a -> a (ignored)
        [$a, $b, $c, $d, $e] = $this->createTasks(['a', 'b', 'c', 'd', 'e']);
        $this->link($a, $b);
        $this->link($a, $c);
        $this->link($b, $d);
        $this->link($c, $d);
        $this->link($a, $a);
        $d->addErrorTaskConfiguration($e);
        $e->addPreviousTaskConfiguration($d);

        self::assertTrue($d->hasAncestor($a));
        self::assertTrue($e->hasAncestor($b));
        self::assertFalse($b->hasAncestor($c));
        self::assertFalse($a->hasAncestor($a));
        self::assertTrue($a->hasDescendant($d));
        self::assertTrue($a->hasDescendant($e));
        self::assertFalse($a->hasDescendant($e, false));
        self::assertFalse($d->hasDescendant($a));
        self::assertFalse($a->hasDescendant($a));
    }

    /**
     * Each task is visited once: a chain of "diamonds" was visited once per path (2^n).
     */
    public function testChainOfDiamondsIsSearchedInLinearTime(): void
    {
        $tasks = $this->createTasks(array_map(static fn (int $i): string => "t{$i}", range(0, 90)));
        for ($i = 0; $i + 3 < \count($tasks); $i += 3) {
            $this->link($tasks[$i], $tasks[$i + 1]);
            $this->link($tasks[$i], $tasks[$i + 2]);
            $this->link($tasks[$i + 1], $tasks[$i + 3]);
            $this->link($tasks[$i + 2], $tasks[$i + 3]);
        }

        $start = microtime(true);
        $last = $tasks[90];
        self::assertFalse($last->hasAncestor($last));
        self::assertFalse($tasks[0]->hasDescendant($tasks[0]));
        self::assertLessThan(1, microtime(true) - $start);
    }

    /**
     * A cycle that does not contain the searched task: the search recursed endlessly.
     */
    public function testTheSearchEndsOnACycle(): void
    {
        // b -> c -> b, b -> d
        [$b, $c, $d] = $this->createTasks(['b', 'c', 'd']);
        $this->link($b, $c);
        $this->link($c, $b);
        $this->link($b, $d);

        self::assertFalse($d->hasAncestor($d));
        self::assertTrue($c->hasAncestor($c));
        self::assertTrue($c->hasDescendant($d));
        self::assertFalse($d->hasDescendant($b));
    }

    /**
     * @param list<string> $codes
     *
     * @return list<TaskConfiguration>
     */
    private function createTasks(array $codes): array
    {
        return array_map(static fn (string $code): TaskConfiguration => new TaskConfiguration($code, 'service', []), $codes);
    }

    private function link(TaskConfiguration $previous, TaskConfiguration $next): void
    {
        $previous->addNextTaskConfiguration($next);
        $next->addPreviousTaskConfiguration($previous);
    }
}
