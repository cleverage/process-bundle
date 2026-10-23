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
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessConfigurationRegistry::class)]
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
}
