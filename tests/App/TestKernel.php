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

namespace CleverAge\ProcessBundle\Tests\App;

use CleverAge\ProcessBundle\CleverAgeProcessBundle;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

/**
 * Application used by the integration tests: the bundle with FrameworkBundle, a process referencing every task of the
 * bundle by its code and a process to run, in a temporary directory of its own (per PHP process).
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new CleverAgeProcessBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getRunDir().'/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getRunDir().'/logs';
    }

    /**
     * Codes of the tasks of the bundle (cleverage.task tags of config/services/task.yaml).
     *
     * @return list<string>
     */
    public static function getTaskCodes(): array
    {
        /** @var array{services: array<string, array{tags?: list<array{name?: string, code?: string}|string>}|string|null>} $config */
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/config/services/task.yaml', Yaml::PARSE_CUSTOM_TAGS);
        $codes = [];
        foreach ($config['services'] as $service) {
            foreach (\is_array($service) ? $service['tags'] ?? [] : [] as $tag) {
                if (\is_array($tag) && 'cleverage.task' === ($tag['name'] ?? null) && isset($tag['code'])) {
                    $codes[] = $tag['code'];
                }
            }
        }

        return $codes;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'validation' => ['email_validation_mode' => 'html5'],
        ]);
        if (Kernel::VERSION_ID >= 70300) {
            // Not setting it is deprecated since Symfony 7.3: its default value changes in 8.0
            $container->extension('framework', ['property_info' => ['with_constructor_extractor' => true]]);
        }

        $tasks = [];
        foreach (self::getTaskCodes() as $code) {
            $tasks[$code] = ['task' => $code];
        }
        $container->extension('clever_age_process', [
            'configurations' => [
                'test.every_task' => ['tasks' => $tasks],
                'test.run' => [
                    'end_point' => 'format',
                    'tasks' => [
                        'constant' => [
                            'task' => 'constant_output',
                            'options' => ['output' => 'world'],
                            'outputs' => ['format'],
                        ],
                        'format' => [
                            'task' => 'transformer',
                            'options' => ['transformers' => ['sprintf' => ['format' => 'Hello %s']]],
                        ],
                    ],
                ],
            ],
        ]);

        $services = $container->services();
        // The default logger of FrameworkBundle may write the process logs to the output
        $services->set('logger', NullLogger::class);
        // Exposed to the tests
        $services->alias('test.task_registry', TaskRegistry::class)->public();
        $services->alias('test.transformer_registry', TransformerRegistry::class)->public();
        $services->alias('test.process_configuration_registry', ProcessConfigurationRegistry::class)->public();
        $services->alias('test.process_manager', ProcessManager::class)->public();
    }

    private function getRunDir(): string
    {
        return sys_get_temp_dir().'/process_bundle_tests_'.getmypid();
    }
}
