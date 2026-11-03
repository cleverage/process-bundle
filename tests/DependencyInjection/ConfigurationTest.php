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

namespace CleverAge\ProcessBundle\Tests\DependencyInjection;

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use CleverAge\ProcessBundle\DependencyInjection\CleverAgeProcessExtension;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use CleverAge\ProcessBundle\Tests\IgnoreDeprecationsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[\PHPUnit\Framework\Attributes\CoversClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(CleverAgeProcessExtension::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(DeprecatedCodeAliases::class)]
class ConfigurationTest extends TestCase
{
    use IgnoreDeprecationsTrait;

    public function testLogLevelsDefaults(): void
    {
        $config = $this->process(['configurations' => ['test.process' => ['tasks' => []]]]);

        self::assertSame(['success_level' => 'info', 'failed_level' => 'debug'], $config['logs']);
        self::assertSame(
            ['success_level' => null, 'failed_level' => null],
            $config['configurations']['test.process']['logs']
        );
    }

    public function testLogLevelsCanBeConfigured(): void
    {
        $config = $this->process([
            'logs' => ['success_level' => 'debug', 'failed_level' => 'error'],
            'configurations' => [
                'test.process' => [
                    'logs' => ['failed_level' => 'critical'],
                    'tasks' => [],
                ],
            ],
        ]);

        self::assertSame(['success_level' => 'debug', 'failed_level' => 'error'], $config['logs']);
        self::assertEquals(
            ['success_level' => null, 'failed_level' => 'critical'],
            $config['configurations']['test.process']['logs']
        );
    }

    public function testTaskIsDefinedByAServiceOrACode(): void
    {
        // Deprecated "service" option, see testServiceIsDeprecated()
        $config = self::ignoreDeprecations(fn (): array => $this->process(['configurations' => ['test.process' => ['tasks' => [
            'by_service' => ['service' => '@app.task'],
            'by_code' => ['task' => 'transformer'],
        ]]]]));

        $tasks = $config['configurations']['test.process']['tasks'];
        self::assertSame(['@app.task', null], [$tasks['by_service']['service'], $tasks['by_service']['task']]);
        self::assertSame([null, 'transformer'], [$tasks['by_code']['service'], $tasks['by_code']['task']]);
    }

    public function testServiceIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $this->process(['configurations' => ['test.process' => ['tasks' => [
                'by_service' => ['service' => '@app.task'],
                'by_code' => ['task' => 'transformer'],
            ]]]]);
        } finally {
            restore_error_handler();
        }

        self::assertSame(
            ['Since cleverage/process-bundle 5.4: The "service" option of the task "clever_age_process.configurations.test.process.tasks.by_service" is deprecated, reference the task by its code instead ("task: <code>", given on the reference page of each task; give a code to your own tasks with #[AsTask] or a "cleverage.task" tag). It will be removed in v6.0.'],
            $deprecations
        );
    }

    public function testErrorsIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $config = $this->process(['configurations' => ['test.process' => ['tasks' => [
                'with_errors' => ['task' => 'transformer', 'errors' => 'error'],
                'with_error_outputs' => ['task' => 'transformer', 'error_outputs' => 'error'],
                'error' => ['task' => 'transformer'],
            ]]]]);
        } finally {
            restore_error_handler();
        }

        self::assertSame(
            ['Since cleverage/process-bundle 5.4: The "errors" option of the task "clever_age_process.configurations.test.process.tasks.with_errors" is deprecated, use "error_outputs" instead. It will be removed in v6.0.'],
            $deprecations
        );
        self::assertSame(['error'], $config['configurations']['test.process']['tasks']['with_errors']['errors']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidTaskDefinitions(): iterable
    {
        yield 'neither service nor task' => [['options' => []]];
        yield 'both service and task' => [['service' => '@app.task', 'task' => 'transformer']];
    }

    /**
     * @param array<string, mixed> $task
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidTaskDefinitions')]
    public function testTaskMustDefineEitherAServiceOrACode(array $task): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A task must define either its code ("task: <code>") or, deprecated, its "service", not both');

        // Deprecated "service" option, see testServiceIsDeprecated()
        self::ignoreDeprecations(fn (): array => $this->process(['configurations' => ['test.process' => ['tasks' => ['invalid' => $task]]]]));
    }

    public function testInvalidDefaultLogLevelIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['logs' => ['success_level' => 'verbose']]);
    }

    public function testInvalidProcessLogLevelIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['configurations' => ['test.process' => ['logs' => ['failed_level' => 'verbose'], 'tasks' => []]]]);
    }

    public function testDefaultLogLevelsArePassedToTheProcessConfigurationRegistry(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([['logs' => ['success_level' => 'debug']]], $container);

        self::assertSame(
            ['success_level' => 'debug', 'failed_level' => 'debug'],
            $container->getDefinition('cleverage_process.registry.process_configuration')->getArgument(2)
        );
    }

    public function testUnknownErrorStrategyIsDeprecated(): void
    {
        $container = new ContainerBuilder();
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            (new CleverAgeProcessExtension())->load([['configurations' => ['test.process' => ['tasks' => [
                'default' => ['task' => 'transformer'],
                'skip' => ['task' => 'transformer', 'error_strategy' => 'skip'],
                'stop' => ['task' => 'transformer', 'error_strategy' => 'stop'],
                // Placeholder of the environment variable, as given to the extension
                'env' => ['task' => 'transformer', 'error_strategy' => $container->getParameterBag()->resolveValue('%env(APP_ERROR_STRATEGY)%')],
                'typo' => ['task' => 'transformer', 'error_strategy' => 'skp'],
            ]]]]], $container);
        } finally {
            restore_error_handler();
        }

        self::assertSame(
            ['Since cleverage/process-bundle 5.4: The error strategy "skp" of the task "typo" of the process "test.process" is unknown, use "skip" or "stop" (or remove it to use the "default_error_strategy"). Unknown error strategies are deprecated, they will be rejected in v6.0.'],
            $deprecations
        );
    }

    public function testUnknownTaskReferenceIsDeprecated(): void
    {
        $container = new ContainerBuilder();
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            (new CleverAgeProcessExtension())->load([['configurations' => [
                'test.valid' => [
                    'entry_point' => 'first-task',
                    'end_point' => 'last',
                    'tasks' => [
                        // Former name of "next-task": deprecated on its own when the process is resolved
                        'first-task' => ['task' => 'transformer', 'outputs' => ['next_task'], 'error_outputs' => ['last']],
                        'next-task' => ['task' => 'transformer', 'outputs' => ['last']],
                        // Placeholder of the environment variable, as given to the extension
                        'env' => ['task' => 'transformer', 'outputs' => [$container->getParameterBag()->resolveValue('%env(APP_NEXT_TASK)%')]],
                        'last' => ['task' => 'transformer'],
                    ],
                ],
                'test.invalid' => [
                    'entry_point' => 'missing_entry',
                    'end_point' => 'missing_end',
                    'tasks' => [
                        'read' => ['task' => 'transformer', 'outputs' => ['missing', 'write', 'missing'], 'error_outputs' => ['missing_error']],
                        'write' => ['task' => 'transformer', 'errors' => ['missing_deprecated_error']],
                    ],
                ],
            ]]], $container);
        } finally {
            restore_error_handler();
        }

        $suffix = ' is not a task of this process. Unknown task references are deprecated, they will be rejected when the container is compiled in v6.0 (the process already fails when it is executed).';
        self::assertSame(
            [
                'Since cleverage/process-bundle 5.8: The task "missing" in the "outputs" of the task "read" of the process "test.invalid"'.$suffix,
                'Since cleverage/process-bundle 5.8: The task "missing_error" in the "error_outputs" of the task "read" of the process "test.invalid"'.$suffix,
                'Since cleverage/process-bundle 5.8: The task "missing_deprecated_error" in the "errors" of the task "write" of the process "test.invalid"'.$suffix,
                'Since cleverage/process-bundle 5.8: The task "missing_entry" in the "entry_point" of the process "test.invalid"'.$suffix,
                'Since cleverage/process-bundle 5.8: The task "missing_end" in the "end_point" of the process "test.invalid"'.$suffix,
            ],
            array_values(array_filter($deprecations, static fn (string $message): bool => str_contains($message, 'Unknown task references')))
        );
    }

    public function testPublicMustBeABoolean(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid type for path "clever_age_process.configurations.test.process.public". Expected "bool", but got "string".');

        $this->process(['configurations' => ['test.process' => ['public' => 'no', 'tasks' => []]]]);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    /**
     * The dashes of the codes were replaced by underscores (key normalization), not those of the references to them.
     */
    public function testTheDashesOfTheCodesAreKept(): void
    {
        $config = $this->process([
            'generic_transformers' => ['to-upper' => ['transformers' => ['callback' => ['callback' => 'mb_strtoupper']]]],
            'configurations' => ['demo.x-y' => ['tasks' => [
                'first-task' => ['task' => 'transformer', 'outputs' => ['next-task']],
                'next-task' => ['task' => 'transformer'],
            ]]],
        ]);

        self::assertSame(['to-upper'], array_keys($config['generic_transformers']));
        self::assertSame(['demo.x-y'], array_keys($config['configurations']));
        self::assertSame(['first-task', 'next-task'], array_keys($config['configurations']['demo.x-y']['tasks']));
        // The keys of the options are still normalized
        self::assertArrayHasKey('error_outputs', $this->process(['configurations' => ['p' => ['tasks' => [
            't' => ['task' => 'transformer', 'error-outputs' => ['u']],
            'u' => ['task' => 'transformer'],
        ]]]])['configurations']['p']['tasks']['t']);
    }
}
