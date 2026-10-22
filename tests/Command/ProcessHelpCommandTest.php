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

use CleverAge\ProcessBundle\Command\ProcessHelpCommand;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(ProcessHelpCommand::class)]
#[UsesClass(ProcessConfigurationRegistry::class)]
#[UsesClass(TaskRegistry::class)]
class ProcessHelpCommandTest extends TestCase
{
    public function testWithContainerIsDeprecated(): void
    {
        self::assertSame(
            ['Since cleverage/process-bundle 5.4: Passing a service container to "'.ProcessHelpCommand::class.'::__construct()" is deprecated, pass a "'.TaskRegistry::class.'" instead: the tasks are fetched from the service container. Only a "'.TaskRegistry::class.'" will be accepted in v6.0.'],
            $this->collectDeprecations(static fn (): ProcessHelpCommand => new ProcessHelpCommand(new ProcessConfigurationRegistry([], 'stop'), new Container()))
        );
    }

    public function testWithTaskRegistryIsNotDeprecated(): void
    {
        self::assertSame(
            [],
            $this->collectDeprecations(static fn (): ProcessHelpCommand => new ProcessHelpCommand(new ProcessConfigurationRegistry([], 'stop'), new TaskRegistry(new ServiceLocator([]))))
        );
    }

    /**
     * @param callable(): mixed $callable
     *
     * @return list<string>
     */
    private function collectDeprecations(callable $callable): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
