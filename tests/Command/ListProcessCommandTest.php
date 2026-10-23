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

namespace CleverAge\ProcessBundle\Tests\Command;

use CleverAge\ProcessBundle\Command\ListProcessCommand;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ListProcessCommand::class)]
#[UsesClass(ProcessConfiguration::class)]
#[UsesClass(TaskConfiguration::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(InvalidProcessConfigurationException::class)]
class ListProcessCommandTest extends TestCase
{
    public function testThePublicProcessesAreListedSortedByCode(): void
    {
        $tester = $this->createTester();

        self::assertSame(0, $tester->execute([]));
        self::assertSame(
            [
                'There are 2 process configurations defined (and 1 private) :',
                ' - test.a with 1 tasks   First process',
                ' - test.b with 0 tasks',
            ],
            $this->lines($tester->getDisplay())
        );
    }

    public function testAllTheProcessesAreListedWithTheAllOption(): void
    {
        $tester = $this->createTester();

        self::assertSame(0, $tester->execute(['--all' => true]));
        self::assertSame(
            [
                'There are 2 process configurations defined (and 1 private) :',
                ' - test.a with 1 tasks                   First process',
                ' - test.b with 0 tasks',
                ' - test.private with 0 tasks (private)   Private process',
            ],
            $this->lines($tester->getDisplay())
        );
    }

    /**
     * @return list<string>
     */
    private function lines(string $display): array
    {
        return array_values(array_filter(array_map(rtrim(...), explode("\n", $display))));
    }

    private function createTester(): CommandTester
    {
        $process = static fn (string $description, bool $public, array $tasks = []): array => [
            'options' => [],
            'entry_point' => null,
            'end_point' => null,
            'description' => $description,
            'help' => '',
            'public' => $public,
            'tasks' => $tasks,
        ];
        $registry = new ProcessConfigurationRegistry(
            [
                'test.private' => $process('Private process', false),
                'test.b' => $process('', true),
                // Left out of the list (with an error log) instead of making the command fail
                'test.invalid' => ['entry_point' => 'missing'] + $process('Invalid process', true),
                'test.a' => $process('First process', true, [
                    'task' => [
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
                ]),
            ],
            'stop'
        );

        return new CommandTester(new ListProcessCommand($registry));
    }
}
