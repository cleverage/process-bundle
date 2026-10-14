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

use CleverAge\ProcessBundle\DependencyInjection\CleverAgeProcessExtension;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[\PHPUnit\Framework\Attributes\CoversClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(CleverAgeProcessExtension::class)]
class ConfigurationTest extends TestCase
{
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
        $config = $this->process(['configurations' => ['test.process' => ['tasks' => [
            'by_service' => ['service' => '@app.task'],
            'by_code' => ['task' => 'transformer'],
        ]]]]);

        $tasks = $config['configurations']['test.process']['tasks'];
        self::assertSame(['@app.task', null], [$tasks['by_service']['service'], $tasks['by_service']['task']]);
        self::assertSame([null, 'transformer'], [$tasks['by_code']['service'], $tasks['by_code']['task']]);
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
        $this->expectExceptionMessage('A task must define either a "service" (service id or class name) or a "task" (code of the task), not both');

        $this->process(['configurations' => ['test.process' => ['tasks' => ['invalid' => $task]]]]);
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

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
