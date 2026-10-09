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

namespace CleverAge\ProcessBundle\Tests\Task\Serialization;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[\PHPUnit\Framework\Attributes\CoversClass(DenormalizerTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class DenormalizerTaskTest extends TestCase
{
    public function testInputIsDenormalizedIntoAnObjectWithProperties(): void
    {
        $state = $this->execute(['class' => DenormalizerTaskTestDto::class], ['name' => 'Foo', 'price' => 12]);

        $output = $state->getOutput();
        self::assertInstanceOf(DenormalizerTaskTestDto::class, $output);
        self::assertSame('Foo', $output->name);
        self::assertSame(12, $output->price);
    }

    public function testListOfObjectsIsDenormalized(): void
    {
        $state = $this->execute(
            ['class' => DenormalizerTaskTestDto::class.'[]'],
            [['name' => 'Foo', 'price' => 1], ['name' => 'Bar', 'price' => 2]],
        );

        $output = $state->getOutput();
        self::assertIsArray($output);
        self::assertCount(2, $output);
        self::assertContainsOnlyInstancesOf(DenormalizerTaskTestDto::class, $output);
        self::assertSame('Bar', $output[1]->name);
    }

    public function testOptionsArePassedToTheDenormalizer(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::once())
            ->method('denormalize')
            ->with(['foo' => 'bar'], 'App\Foo', 'json', ['groups' => ['import']])
            ->willReturn('denormalized');

        $state = $this->execute(
            ['class' => 'App\Foo', 'format' => 'json', 'context' => ['groups' => ['import']]],
            ['foo' => 'bar'],
            $denormalizer,
        );

        self::assertSame('denormalized', $state->getOutput());
    }

    public function testDefaultFormatAndContextArePassedToTheDenormalizer(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::once())
            ->method('denormalize')
            ->with('input', 'App\Foo', null, [])
            ->willReturn('denormalized');

        $state = $this->execute(['class' => 'App\Foo'], 'input', $denormalizer);

        self::assertSame('denormalized', $state->getOutput());
    }

    public function testContextIsUsedByTheDenormalizer(): void
    {
        $existing = new DenormalizerTaskTestDto();
        $existing->price = 99;

        $state = $this->execute(
            ['class' => DenormalizerTaskTestDto::class, 'context' => [AbstractNormalizer::OBJECT_TO_POPULATE => $existing]],
            ['name' => 'Foo'],
        );

        self::assertSame($existing, $state->getOutput());
        self::assertSame('Foo', $existing->name);
        self::assertSame(99, $existing->price);
    }

    public function testDenormalizationFailureIsPropagated(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->execute(['class' => DenormalizerTaskTestDto::class], ['name' => 'Foo', 'price' => 'not an int']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'missing class' => [[], MissingOptionsException::class];
        yield 'class not a string' => [['class' => 42], InvalidOptionsException::class];
        yield 'format not a string' => [['class' => 'App\Foo', 'format' => 42], InvalidOptionsException::class];
        yield 'context not an array' => [['class' => 'App\Foo', 'context' => 'foo'], InvalidOptionsException::class];
        yield 'unknown option' => [['class' => 'App\Foo', 'unknown' => true], UndefinedOptionsException::class];
    }

    /**
     * @param array<string, mixed>     $options
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrowAtInitialization(array $options, string $exception): void
    {
        $task = new DenormalizerTask($this->createStub(DenormalizerInterface::class));

        $this->expectException($exception);
        $task->initialize($this->createState($options, null));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function execute(array $options, mixed $input, ?DenormalizerInterface $denormalizer = null): ProcessState
    {
        $state = $this->createState($options, $input);
        $task = new DenormalizerTask($denormalizer ?? new Serializer([new ArrayDenormalizer(), new ObjectNormalizer()]));
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createState(array $options, mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('denormalize', DenormalizerTask::class, $options));
        $state->reset(false);
        $state->setInput($input);

        return $state;
    }
}

final class DenormalizerTaskTestDto
{
    public ?string $name = null;

    public ?int $price = null;
}
