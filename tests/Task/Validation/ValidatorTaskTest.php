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

namespace CleverAge\ProcessBundle\Tests\Task\Validation;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Validation\ValidatorTask;
use CleverAge\ProcessBundle\Validator\ConstraintLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

#[\PHPUnit\Framework\Attributes\CoversClass(ValidatorTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ConstraintLoader::class)]
class ValidatorTaskTest extends TestCase
{
    /** @var list<array{level: mixed, message: string, context: array<mixed>}> */
    private array $records = [];

    /**
     * Same scenario as the legacy kernel-based test (test.validator_task).
     */
    public function testValidInputIsForwardedUnchanged(): void
    {
        $input = [
            'int_field' => 42,
            'any_field' => 'hello',
            'choice_field' => 'Some random value 1',
        ];

        $state = $this->execute(['constraints' => $this->collectionConstraints()], $input);

        self::assertSame($input, $state->getOutput());
        self::assertFalse($state->isSkipped());
        self::assertFalse($state->hasErrorOutput());
        self::assertSame([], $this->records);
    }

    public function testInvalidInputThrowsAndLogsEachViolationAtCriticalLevelByDefault(): void
    {
        $input = ['int_field' => 'not an int', 'any_field' => 'hello', 'choice_field' => 'unknown'];

        try {
            $this->execute(['constraints' => $this->collectionConstraints()], $input);
            self::fail('An exception should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame('2 constraint violations detected on validation', $e->getMessage());
        }

        self::assertCount(2, $this->records);
        self::assertSame([LogLevel::CRITICAL, LogLevel::CRITICAL], array_column($this->records, 'level'));
        self::assertSame(
            ['property' => '[int_field]', 'violation_code' => Assert\Type::INVALID_TYPE_ERROR, 'invalid_value' => 'not an int'],
            $this->records[0]['context']
        );
        self::assertSame('[choice_field]', $this->records[1]['context']['property']);
        self::assertSame('unknown', $this->records[1]['context']['invalid_value']);
        self::assertSame(Assert\Choice::NO_SUCH_CHOICE_ERROR, $this->records[1]['context']['violation_code']);
    }

    public function testErrorOutputViolationsSkipsTheTaskAndSendsViolationsToTheErrorOutput(): void
    {
        $state = $this->execute(['constraints' => [['NotBlank' => null]], 'error_output_violations' => true], '');

        self::assertTrue($state->isSkipped());
        self::assertNull($state->getOutput());
        $violations = $state->getErrorOutput();
        self::assertInstanceOf(ConstraintViolationListInterface::class, $violations);
        self::assertCount(1, $violations);
        self::assertSame(NotBlank::IS_BLANK_ERROR, $violations->get(0)->getCode());
        self::assertCount(1, $this->records);
    }

    public function testErrorOutputViolationsDoesNothingSpecialOnValidInput(): void
    {
        $state = $this->execute(['constraints' => [['NotBlank' => null]], 'error_output_violations' => true], 'foo');

        self::assertSame('foo', $state->getOutput());
        self::assertFalse($state->isSkipped());
        self::assertFalse($state->hasErrorOutput());
    }

    /**
     * @return iterable<string, array{string|bool, string|null}>
     */
    public static function logErrorsProvider(): iterable
    {
        yield 'warning' => [LogLevel::WARNING, LogLevel::WARNING];
        yield 'debug' => [LogLevel::DEBUG, LogLevel::DEBUG];
        yield 'emergency' => [LogLevel::EMERGENCY, LogLevel::EMERGENCY];
        yield 'true means critical' => [true, LogLevel::CRITICAL];
        yield 'false disables logging' => [false, null];
    }

    #[DataProvider('logErrorsProvider')]
    public function testLogErrorsSetsTheLogLevel(string|bool $logErrors, ?string $expectedLevel): void
    {
        $state = $this->execute(
            ['constraints' => [['NotBlank' => null]], 'error_output_violations' => true, 'log_errors' => $logErrors],
            '',
        );

        self::assertTrue($state->isSkipped());
        self::assertSame(null === $expectedLevel ? [] : [$expectedLevel], array_column($this->records, 'level'));
    }

    public function testConstraintsOptionsAndNestedConstraintsAreBuilt(): void
    {
        $constraints = [
            ['Collection' => [
                'allowExtraFields' => true,
                'fields' => [
                    'sku' => [['NotBlank' => null]],
                    'price' => [['Type' => 'numeric'], ['PositiveOrZero' => null]],
                ],
            ]],
        ];

        $valid = $this->execute(['constraints' => $constraints], ['sku' => 'A1', 'price' => 10, 'extra' => true]);
        self::assertSame(['sku' => 'A1', 'price' => 10, 'extra' => true], $valid->getOutput());

        $invalid = $this->execute(
            ['constraints' => $constraints, 'error_output_violations' => true],
            ['sku' => '', 'price' => -1],
        );
        $violations = $invalid->getErrorOutput();
        self::assertInstanceOf(ConstraintViolationListInterface::class, $violations);
        self::assertCount(2, $violations);
        self::assertSame('[sku]', $violations->get(0)->getPropertyPath());
        self::assertSame('[price]', $violations->get(1)->getPropertyPath());
    }

    public function testConstraintCanBeReferencedByFqcn(): void
    {
        $state = $this->execute(
            ['constraints' => [[NotBlank::class => null]], 'error_output_violations' => true],
            '',
        );

        self::assertTrue($state->isSkipped());
    }

    public function testNullConstraintsUseTheInputClassMetadata(): void
    {
        $state = $this->execute(['error_output_violations' => true], new ValidatorTaskTestEntity());

        self::assertTrue($state->isSkipped());
        $violations = $state->getErrorOutput();
        self::assertInstanceOf(ConstraintViolationListInterface::class, $violations);
        self::assertCount(1, $violations);
        self::assertSame('name', $violations->get(0)->getPropertyPath());
    }

    public function testValidEntityIsForwardedUnchanged(): void
    {
        $entity = new ValidatorTaskTestEntity();
        $entity->name = 'Foo';
        $entity->sku = 'A1';

        $state = $this->execute(['groups' => ['Default', 'import']], $entity);

        self::assertSame($entity, $state->getOutput());
    }

    public function testGroupsOptionSelectsTheValidatedConstraints(): void
    {
        $entity = new ValidatorTaskTestEntity();
        $entity->name = 'Foo';

        $default = $this->execute(['error_output_violations' => true], $entity);
        self::assertFalse($default->isSkipped());
        self::assertSame($entity, $default->getOutput());

        $import = $this->execute(['groups' => ['import'], 'error_output_violations' => true], $entity);
        self::assertTrue($import->isSkipped());
        $violations = $import->getErrorOutput();
        self::assertInstanceOf(ConstraintViolationListInterface::class, $violations);
        self::assertCount(1, $violations);
        self::assertSame('sku', $violations->get(0)->getPropertyPath());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'unknown log level' => [['log_errors' => 'verbose'], InvalidOptionsException::class];
        yield 'groups not an array' => [['groups' => 'import'], InvalidOptionsException::class];
        yield 'constraints not an array' => [['constraints' => 'NotBlank'], InvalidOptionsException::class];
        yield 'error_output_violations not a bool' => [['error_output_violations' => 1], InvalidOptionsException::class];
        yield 'unknown option' => [['unknown' => true], UndefinedOptionsException::class];
    }

    /**
     * @param array<string, mixed>     $options
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrowAtInitialization(array $options, string $exception): void
    {
        $this->expectException($exception);
        $this->createTask()->initialize($this->createState($options, null));
    }

    /**
     * @return array<mixed>
     */
    private function collectionConstraints(): array
    {
        return [
            ['Collection' => [
                'fields' => [
                    'int_field' => [['NotNull' => null], ['Type' => 'int']],
                    'any_field' => [['NotBlank' => null]],
                    'choice_field' => [['Choice' => ['choices' => ['Some random value 1', 'Some random value 2']]]],
                ],
            ]],
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function execute(array $options, mixed $input): ProcessState
    {
        $state = $this->createState($options, $input);
        $task = $this->createTask();
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    private function createTask(): ValidatorTask
    {
        $logger = new class(function (mixed $level, string $message, array $context): void {
            $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
        }) extends AbstractLogger {
            public function __construct(private readonly \Closure $onLog)
            {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ($this->onLog)($level, (string) $message, $context);
            }
        };

        return new ValidatorTask($logger, Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
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
        $state->setTaskConfiguration(new TaskConfiguration('validate', ValidatorTask::class, $options));
        $state->reset(false);
        $state->setInput($input);

        return $state;
    }
}

final class ValidatorTaskTestEntity
{
    #[NotBlank]
    public ?string $name = null;

    #[NotBlank(groups: ['import'])]
    public ?string $sku = null;
}
