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

use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessConfigurationRegistry::class)]
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
}
