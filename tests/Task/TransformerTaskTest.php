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
use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Task\TransformerTask;
use CleverAge\ProcessBundle\Transformer\Array\ArrayLastTransformer;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\MappingTransformer;
use CleverAge\ProcessBundle\Transformer\String\TrimTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(TransformerTask::class)]
#[\PHPUnit\Framework\Attributes\UsesTrait(TransformerTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(MissingTransformerException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ArrayLastTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CallbackTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(MappingTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TrimTransformer::class)]
class TransformerTaskTest extends TestCase
{
    public function testInputIsOutputUnchangedWithoutTransformers(): void
    {
        $input = ['foo' => 'bar'];

        self::assertSame($input, $this->execute([], $input)->getOutput());
        self::assertSame($input, $this->execute(['transformers' => []], $input)->getOutput());
    }

    public function testSingleTransformer(): void
    {
        $state = $this->execute([
            'transformers' => [
                'callback' => ['callback' => 'json_decode', 'right_parameters' => [true]],
            ],
        ], '{"foo":"bar"}');

        self::assertNull($state->getException());
        self::assertSame(['foo' => 'bar'], $state->getOutput());
    }

    public function testSimpleMapping(): void
    {
        $state = $this->execute([
            'transformers' => [
                'mapping' => ['mapping' => ['field' => ['code' => '.']]],
            ],
        ], 'value');

        self::assertSame(['field' => 'value'], $state->getOutput());
    }

    public function testMappingWithSubTransformers(): void
    {
        $state = $this->execute([
            'transformers' => [
                'mapping' => [
                    'mapping' => [
                        'name' => ['code' => '[firstname]', 'transformers' => ['trim' => null]],
                    ],
                ],
            ],
        ], ['firstname' => '  John ']);

        self::assertSame(['name' => 'John'], $state->getOutput());
    }

    public function testSameTransformerCanBeChainedWithSuffixes(): void
    {
        $state = $this->execute([
            'transformers' => [
                'callback#1' => ['callback' => 'array_filter'],
                'callback#reverse' => ['callback' => 'array_reverse'],
            ],
        ], [3, null, 4, 2]);

        self::assertSame([2, 4, 3], $state->getOutput());
    }

    public function testNonConfigurableTransformerAcceptsNullOptions(): void
    {
        $state = $this->execute(['transformers' => ['array_last' => null]], [1, 2, 3]);

        self::assertSame(3, $state->getOutput());
    }

    public function testTransformerFailureSetsExceptionWithErrorContext(): void
    {
        $state = $this->execute([
            'transformers' => [
                'callback#1' => ['callback' => 'intval'],
                'callback#2' => ['callback' => 'intdiv', 'right_parameters' => [0]],
            ],
        ], ' 1 ');

        $exception = $state->getException();
        self::assertInstanceOf(TransformerException::class, $exception);
        self::assertInstanceOf(\DivisionByZeroError::class, $exception->getPrevious());
        self::assertSame("Transformation 'callback#2' have failed: Division by zero", $exception->getMessage());
        self::assertSame(['error' => 'Division by zero'], $state->getErrorContext());
        self::assertNull($state->getOutput());
    }

    public function testMissingMappedPropertyFails(): void
    {
        $state = $this->execute([
            'transformers' => [
                'mapping' => ['mapping' => ['field' => ['code' => 'missing']]],
            ],
        ], (object) ['other' => 'value']);

        self::assertInstanceOf(TransformerException::class, $state->getException());
        self::assertInstanceOf(NoSuchPropertyException::class, $state->getException()->getPrevious());
        self::assertNull($state->getOutput());
    }

    public function testUnknownTransformerIsRejectedAtInitialization(): void
    {
        $this->expectException(MissingTransformerException::class);
        $this->expectExceptionMessage('No transformer with code : unknown');

        $this->execute(['transformers' => ['unknown' => null]], 'value');
    }

    public function testSuffixOfUnknownTransformerIsNotStripped(): void
    {
        $this->expectException(MissingTransformerException::class);
        $this->expectExceptionMessage('No transformer with code : unknown#1');

        $this->execute(['transformers' => ['unknown#1' => null]], 'value');
    }

    public function testNonConfigurableTransformerCannotHaveOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transformer array_last should not have options');

        $this->execute(['transformers' => ['array_last' => ['foo' => 'bar']]], [1]);
    }

    public function testScalarTransformerOptionsAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Options for transformer trim#1 are invalid : found string, expected array or null');

        $this->execute(['transformers' => ['trim#1' => 'x']], 'value');
    }

    public function testInvalidTransformerOptionsAreRejected(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->execute(['transformers' => ['callback' => []]], 'value');
    }

    public function testTransformersOptionMustBeAnArray(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->execute(['transformers' => 'trim'], 'value');
    }

    private function execute(array $options, mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('transform', TransformerTask::class, $options));
        $state->setInput($input);

        $registry = new TransformerRegistry();
        $registry->addTransformer(new ArrayLastTransformer());
        $registry->addTransformer(new CallbackTransformer());
        $registry->addTransformer(new TrimTransformer());
        $registry->addTransformer(new MappingTransformer($registry, new NullLogger(), PropertyAccess::createPropertyAccessor()));

        $task = new TransformerTask(new NullLogger(), $registry);
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }
}
