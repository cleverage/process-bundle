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
use CleverAge\ProcessBundle\Task\PropertyGetterTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(PropertyGetterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class PropertyGetterTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{string, mixed, mixed}>
     */
    public static function readableProvider(): iterable
    {
        yield 'array index' => ['[sku]', ['sku' => 'ABC', 'name' => 'Foo'], 'ABC'];
        yield 'nested array index' => ['[product][sku]', ['product' => ['sku' => 'ABC']], 'ABC'];
        yield 'array value' => ['[tags]', ['tags' => ['a', 'b']], ['a', 'b']];
        yield 'missing array index' => ['[missing]', ['sku' => 'ABC'], null];
        yield 'object property' => ['path', (object) ['path' => '/tmp/foo.csv'], '/tmp/foo.csv'];
        yield 'object then array' => ['data[sku]', (object) ['data' => ['sku' => 'ABC']], 'ABC'];
    }

    #[DataProvider('readableProvider')]
    public function testValueIsReadAtPropertyPath(string $property, mixed $input, mixed $expected): void
    {
        $state = $this->execute($property, $input);

        self::assertNull($state->getException());
        self::assertSame([], $state->getErrorContext());
        self::assertSame($expected, $state->getOutput());
    }

    public function testValueIsReadThroughGetter(): void
    {
        $input = new class {
            public function getName(): string
            {
                return 'Foo';
            }
        };

        $state = $this->execute('name', $input);

        self::assertNull($state->getException());
        self::assertSame('Foo', $state->getOutput());
    }

    public function testMissingObjectPropertySetsExceptionWithErrorContext(): void
    {
        $state = $this->execute('missing', new \stdClass());

        self::assertInstanceOf(NoSuchPropertyException::class, $state->getException());
        self::assertSame(['property' => 'missing'], $state->getErrorContext());
        self::assertNull($state->getOutput());
    }

    public function testPropertyPathOnArraySetsExceptionWithErrorContext(): void
    {
        // A property path (not an index) cannot be read on an array
        $state = $this->execute('sku', ['sku' => 'ABC']);

        self::assertInstanceOf(NoSuchPropertyException::class, $state->getException());
        self::assertSame(['property' => 'sku'], $state->getErrorContext());
        self::assertNull($state->getOutput());
    }

    public function testMissingPropertyOptionIsRejected(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->execute(null, []);
    }

    public function testNonStringPropertyOptionIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->execute(['sku'], []);
    }

    private function execute(mixed $property, mixed $input): ProcessState
    {
        $options = null === $property ? [] : ['property' => $property];
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('get', PropertyGetterTask::class, $options));
        $state->setInput($input);
        $state->reset(false);

        $task = new PropertyGetterTask(new NullLogger(), PropertyAccess::createPropertyAccessor());
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }
}
