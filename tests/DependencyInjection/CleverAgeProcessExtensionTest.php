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

use CleverAge\ProcessBundle\Attribute\AsTask;
use CleverAge\ProcessBundle\Attribute\AsTransformer;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\DependencyInjection\CleverAgeProcessExtension;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\ProcessStopCheckerInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Task\DummyTask;
use CleverAge\ProcessBundle\Tests\Fixtures\AttributedTask;
use CleverAge\ProcessBundle\Tests\Fixtures\AttributedTransformer;
use CleverAge\ProcessBundle\Transformer\String\TrimTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\AttributeAutoconfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(CleverAgeProcessExtension::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AsTransformer::class)]
#[UsesClass(AsTask::class)]
class CleverAgeProcessExtensionTest extends TestCase
{
    public function testEveryServiceDefinitionFileIsLoaded(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        $files = glob(\dirname(__DIR__, 2).'/config/services/*.yaml');
        self::assertIsArray($files);
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $config = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);
            self::assertIsArray($config);
            self::assertIsArray($config['services']);
            foreach (array_keys($config['services']) as $id) {
                if ('_defaults' === $id) {
                    continue;
                }
                if (str_ends_with((string) $id, '\\')) {
                    // Resource: at least one service registered under the namespace prefix
                    $registered = array_filter(
                        array_keys($container->getDefinitions()),
                        static fn (string $definitionId): bool => str_starts_with($definitionId, (string) $id)
                    );
                    self::assertNotEmpty($registered, \sprintf('No service registered for the "%s" resource of %s', $id, basename($file)));
                    continue;
                }
                self::assertTrue($container->has((string) $id), \sprintf('Service "%s" of %s is not registered', $id, basename($file)));
            }
        }
    }

    public function testServicesCanBeAutowiredByClassName(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        foreach ([
            ProcessManager::class => 'cleverage_process.manager.process',
            ProcessConfigurationRegistry::class => 'cleverage_process.registry.process_configuration',
            TransformerRegistry::class => 'cleverage_process.registry.transformer',
            ContextualOptionResolver::class => 'cleverage_process.context.contextual_option_resolver',
        ] as $class => $id) {
            self::assertSame($id, (string) $container->getAlias($class));
            self::assertSame($class, $container->getDefinition($id)->getClass());
        }
    }

    public function testTasksAndTransformersAreAutoconfigured(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        // Application services, autoconfigured
        $container->setDefinition('app.task', (new Definition(DummyTask::class))->setAutoconfigured(true));
        $container->setDefinition('app.shared_task', (new Definition(DummyTask::class))->setAutoconfigured(true)->setShared(true));
        $container->setDefinition('app.transformer', (new Definition(TrimTransformer::class))->setAutoconfigured(true));
        // An application keeping its own "_instanceof" rule, as documented before
        $container->setDefinition('app.instanceof_transformer', (new Definition(TrimTransformer::class))
            ->setAutoconfigured(true)
            ->setInstanceofConditionals([
                TransformerInterface::class => (new ChildDefinition(''))->addTag('cleverage.transformer'),
            ]));
        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $task = $container->getDefinition('app.task');
        self::assertTrue($task->isPublic());
        self::assertFalse($task->isShared());
        self::assertSame([['channel' => 'cleverage_process_task']], $task->getTag('monolog.logger'));
        // An explicit configuration of the service wins
        self::assertTrue($container->getDefinition('app.shared_task')->isShared());

        foreach (['app.transformer', 'app.instanceof_transformer'] as $id) {
            $transformer = $container->getDefinition($id);
            // Registered once in the transformer registry (no duplicate tag)
            self::assertSame([[]], $transformer->getTag('cleverage.transformer'), $id);
            self::assertSame([['channel' => 'cleverage_process_transformer']], $transformer->getTag('monolog.logger'), $id);
        }
        // The bundle transformers (explicitly defined and tagged, not autoconfigured) are tagged once
        self::assertSame([['code' => 'trim']], $container->findDefinition(TrimTransformer::class)->getTag('cleverage.transformer'));
    }

    public function testEveryBundleTaskHasAUniqueCode(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        $tagged = $container->findTaggedServiceIds('cleverage.task');
        self::assertCount(58, $tagged);
        foreach ($tagged as $id => $tags) {
            // The id is the class name, the code its snake case without the "Task" suffix
            $code = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', (string) preg_replace('/Task$/', '', substr((string) strrchr($id, '\\'), 1))));
            self::assertSame([['code' => $code]], $tags, $id);
        }
    }

    public function testAsTaskAttributeRegistersTheCode(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);
        $container->setDefinition('app.attributed_task', (new Definition(AttributedTask::class))->setAutoconfigured(true));

        (new AttributeAutoconfigurationPass())->process($container);
        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        self::assertSame([['code' => 'app_attributed']], $container->getDefinition('app.attributed_task')->getTag('cleverage.task'));
    }

    public function testAsTransformerAttributeRegistersTheCode(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);
        $container->setDefinition('app.attributed', (new Definition(AttributedTransformer::class))->setAutoconfigured(true));

        (new AttributeAutoconfigurationPass())->process($container);
        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $tags = $container->getDefinition('app.attributed')->getTag('cleverage.transformer');
        self::assertContains(['code' => 'app_attributed'], $tags);
    }

    public function testStopCheckersAreInjectedInTheProcessManager(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(ProcessStopCheckerInterface::class, $autoconfigured);
        self::assertSame(['cleverage_process.stop_checker' => [[]]], $autoconfigured[ProcessStopCheckerInterface::class]->getTags());

        $argument = $container->getDefinition('cleverage_process.manager.process')->getArgument(6);
        self::assertInstanceOf(TaggedIteratorArgument::class, $argument);
        self::assertSame('cleverage_process.stop_checker', $argument->getTag());
    }

    public function testTheProcessManagerIsResetBetweenExecutions(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        self::assertSame(
            [['method' => 'reset']],
            $container->getDefinition('cleverage_process.manager.process')->getTag('kernel.reset')
        );
    }
}
