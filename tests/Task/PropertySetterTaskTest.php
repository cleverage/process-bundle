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
use CleverAge\ProcessBundle\Task\PropertySetterTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(PropertySetterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class PropertySetterTaskTest extends TestCase
{
    public function testValuesAreSet(): void
    {
        $state = $this->execute(['[status]' => 'imported', '[enabled]' => true], ['name' => 'Foo']);

        self::assertNull($state->getException());
        self::assertSame(['name' => 'Foo', 'status' => 'imported', 'enabled' => true], $state->getOutput());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function valueProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'int' => [42];
        yield 'array' => [['foo']];
        yield 'bool' => [true];
        yield 'float' => [1.5];
        yield 'null' => [null];
    }

    #[DataProvider('valueProvider')]
    public function testFailureKeepsOriginalExceptionWithErrorContext(mixed $value): void
    {
        // A property path (not an index) cannot be written to an array
        $state = $this->execute(['enabled' => $value], ['name' => 'Foo']);

        self::assertInstanceOf(NoSuchPropertyException::class, $state->getException());
        self::assertSame(['property' => 'enabled', 'value' => $value], $state->getErrorContext());
        self::assertNull($state->getOutput());
    }

    public function testScalarInputFailureKeepsErrorContext(): void
    {
        // The PropertyAccessor throws a \TypeError (not an \Exception) on a scalar input
        $state = $this->execute(['[name]' => 'Foo'], 'not an array');

        self::assertInstanceOf(\TypeError::class, $state->getException());
        self::assertSame(['property' => '[name]', 'value' => 'Foo'], $state->getErrorContext());
        self::assertNull($state->getOutput());
    }

    private function execute(array $values, mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('set', PropertySetterTask::class, ['values' => $values]));
        $state->setInput($input);

        $task = new PropertySetterTask(new NullLogger(), PropertyAccess::createPropertyAccessor());
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }
}
