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

namespace CleverAge\ProcessBundle\Tests;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\ObjectUpdaterTask;
use CleverAge\ProcessBundle\Task\Serialization\DeserializerTask;
use CleverAge\ProcessBundle\Task\Serialization\NormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\SerializerTask;
use CleverAge\ProcessBundle\Task\SimpleBatchTask;
use CleverAge\ProcessBundle\Transformer\Array\ArrayFilterTransformer;
use CleverAge\ProcessBundle\Transformer\ConditionTrait;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use CleverAge\ProcessBundle\Transformer\String\HashTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Serializer\Serializer;

/**
 * Options are validated when resolved, instead of failing later with an unclear error.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(HashTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ArrayFilterTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversTrait(ConditionTrait::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(SimpleBatchTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(NormalizerTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(SerializerTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(DeserializerTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ObjectUpdaterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class OptionAllowedTypesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function invalidTransformerOptionsProvider(): iterable
    {
        yield 'hash raw_output' => [HashTransformer::class, ['algo' => 'md5', 'raw_output' => 'yes']];
        yield 'condition empty' => [ArrayFilterTransformer::class, ['condition' => ['empty' => 'name']]];
        yield 'condition not_empty' => [ArrayFilterTransformer::class, ['condition' => ['not_empty' => 'name']]];
    }

    /**
     * @param class-string<ConfigurableTransformerInterface> $class
     * @param array<string, mixed>                           $options
     */
    #[DataProvider('invalidTransformerOptionsProvider')]
    public function testInvalidTransformerOptionIsRejected(string $class, array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveTransformerOptions($class, $options);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function validTransformerOptionsProvider(): iterable
    {
        yield 'hash raw_output' => [HashTransformer::class, ['algo' => 'md5', 'raw_output' => true]];
        yield 'condition empty' => [ArrayFilterTransformer::class, ['condition' => ['empty' => ['name' => null]]]];
        yield 'condition not_empty' => [ArrayFilterTransformer::class, ['condition' => ['not_empty' => ['name' => null]]]];
    }

    /**
     * @param class-string<ConfigurableTransformerInterface> $class
     * @param array<string, mixed>                           $options
     */
    #[DataProvider('validTransformerOptionsProvider')]
    public function testValidTransformerOptionIsAccepted(string $class, array $options): void
    {
        self::assertNotEmpty($this->resolveTransformerOptions($class, $options));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function invalidTaskOptionsProvider(): iterable
    {
        yield 'simple batch batch_count' => [SimpleBatchTask::class, ['batch_count' => '10']];
        yield 'normalizer context' => [NormalizerTask::class, ['format' => 'json', 'context' => 'groups']];
        yield 'serializer context' => [SerializerTask::class, ['format' => 'json', 'context' => 'groups']];
        yield 'deserializer context' => [DeserializerTask::class, ['type' => 'array', 'format' => 'json', 'context' => 'groups']];
        yield 'object updater property_path' => [ObjectUpdaterTask::class, ['property_path' => ['name']]];
    }

    /**
     * @param class-string<AbstractConfigurableTask> $class
     * @param array<string, mixed>                   $options
     */
    #[DataProvider('invalidTaskOptionsProvider')]
    public function testInvalidTaskOptionIsRejected(string $class, array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->initializeTask($class, $options);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function validTaskOptionsProvider(): iterable
    {
        yield 'simple batch batch_count' => [SimpleBatchTask::class, ['batch_count' => 10]];
        yield 'simple batch null batch_count' => [SimpleBatchTask::class, ['batch_count' => null]];
        yield 'normalizer context' => [NormalizerTask::class, ['format' => 'json', 'context' => ['groups' => ['a']]]];
        yield 'serializer context' => [SerializerTask::class, ['format' => 'json', 'context' => []]];
        yield 'deserializer context' => [DeserializerTask::class, ['type' => 'array', 'format' => 'json', 'context' => []]];
        yield 'object updater string property_path' => [ObjectUpdaterTask::class, ['property_path' => 'name']];
        yield 'object updater PropertyPath property_path' => [ObjectUpdaterTask::class, ['property_path' => new PropertyPath('name')]];
    }

    /**
     * @param class-string<AbstractConfigurableTask> $class
     * @param array<string, mixed>                   $options
     */
    #[DataProvider('validTaskOptionsProvider')]
    public function testValidTaskOptionIsAccepted(string $class, array $options): void
    {
        $this->expectNotToPerformAssertions();

        $this->initializeTask($class, $options);
    }

    /**
     * @param class-string<ConfigurableTransformerInterface> $class
     * @param array<string, mixed>                           $options
     *
     * @return array<string, mixed>
     */
    private function resolveTransformerOptions(string $class, array $options): array
    {
        $transformer = ArrayFilterTransformer::class === $class
            ? new ArrayFilterTransformer(PropertyAccess::createPropertyAccessor())
            : new $class();
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }

    /**
     * @param class-string<AbstractConfigurableTask> $class
     * @param array<string, mixed>                   $options
     */
    private function initializeTask(string $class, array $options): void
    {
        $task = match ($class) {
            NormalizerTask::class, SerializerTask::class, DeserializerTask::class => new $class(new Serializer()),
            ObjectUpdaterTask::class => new ObjectUpdaterTask(PropertyAccess::createPropertyAccessor()),
            default => new $class(),
        };

        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('task', $class, $options));
        $task->initialize($state);
    }
}
