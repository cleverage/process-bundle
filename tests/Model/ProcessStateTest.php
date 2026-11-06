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

namespace CleverAge\ProcessBundle\Tests\Model;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
class ProcessStateTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function errorContextValueProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'int' => [42];
        yield 'array' => [['foo' => 'bar']];
        yield 'bool' => [true];
        yield 'float' => [1.5];
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('errorContextValueProvider')]
    public function testAddErrorContextValueAcceptsAnyValue(mixed $value): void
    {
        $state = $this->createState();

        $state->addErrorContextValue('key', $value);

        self::assertSame(['key' => $value], $state->getErrorContext());
    }

    public function testRemoveErrorContext(): void
    {
        $state = $this->createState();
        $state->addErrorContextValue('kept', 'foo');
        $state->addErrorContextValue(0, null);

        $state->removeErrorContext(0);

        self::assertSame(['kept' => 'foo'], $state->getErrorContext());
    }

    public function testContextIsEmptyUntilDefined(): void
    {
        self::assertSame([], $this->createState()->getContext());
    }

    public function testContextualizedOptionsUseADefaultResolver(): void
    {
        $state = $this->createState();
        $state->setContext(['bar' => 'baz']);
        $state->setTaskConfiguration(new TaskConfiguration('task', 'service', ['foo' => '{{ bar }}']));

        self::assertSame(['foo' => 'baz'], $state->getContextualizedOptions());
    }

    public function testReturnCodeIsDeprecated(): void
    {
        $state = $this->createState();
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $state->setReturnCode(2);
            $returnCode = $state->getReturnCode();
        } finally {
            restore_error_handler();
        }

        $this->assertSame(2, $returnCode);
        $this->assertSame([
            'Since cleverage/process-bundle 5.10: The "CleverAge\\ProcessBundle\\Model\\ProcessState::setReturnCode()" method is deprecated: it is used nowhere (the exit code of the command does not depend on it) and will be removed in 6.0.',
            'Since cleverage/process-bundle 5.10: The "CleverAge\\ProcessBundle\\Model\\ProcessState::getReturnCode()" method is deprecated: it is used nowhere and will be removed in 6.0.',
        ], $deprecations);
    }

    private function createState(): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);

        return new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
    }
}
