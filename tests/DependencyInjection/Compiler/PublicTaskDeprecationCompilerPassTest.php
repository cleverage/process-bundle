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

namespace CleverAge\ProcessBundle\Tests\DependencyInjection\Compiler;

use CleverAge\ProcessBundle\DependencyInjection\CleverAgeProcessExtension;
use CleverAge\ProcessBundle\DependencyInjection\Compiler\PublicTaskDeprecationCompilerPass;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Task\DummyTask;
use CleverAge\ProcessBundle\Tests\Fixtures\AttributedTask;
use CleverAge\ProcessBundle\Tests\Fixtures\PublicTask;
use CleverAge\ProcessBundle\Transformer\String\TrimTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\AliasDeprecatedPublicServicesPass;
use Symfony\Component\DependencyInjection\Compiler\RegisterAutoconfigureAttributesPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(PublicTaskDeprecationCompilerPass::class)]
#[UsesClass(CleverAgeProcessExtension::class)]
#[UsesClass(Configuration::class)]
class PublicTaskDeprecationCompilerPassTest extends TestCase
{
    public function testTasksMadePublicByTheAutoconfigurationAreDeprecated(): void
    {
        $container = $this->compile([
            'app.task' => (new Definition(DummyTask::class))->setAutoconfigured(true),
            // Class given by the id
            AttributedTask::class => (new Definition())->setAutoconfigured(true),
        ]);

        foreach (['app.task', AttributedTask::class] as $id) {
            self::assertTrue($container->hasAlias($id), $id);
            $alias = $container->getAlias($id);
            self::assertTrue($alias->isPublic(), $id);
            self::assertTrue($alias->isDeprecated(), $id);
            self::assertSame(
                ['package' => 'cleverage/process-bundle', 'version' => '5.4', 'message' => 'Accessing the "'.$id.'" service directly from the container is deprecated, use dependency injection instead.'],
                $alias->getDeprecation($id),
            );
            self::assertFalse($container->getDefinition('.container.private.'.$id)->isShared(), $id);
        }
    }

    public function testTasksMadePublicByTheApplicationAreNotDeprecated(): void
    {
        $container = $this->compile([
            // Service or "_defaults" configuration
            'app.public_task' => (new Definition(DummyTask::class))->setAutoconfigured(true)->setPublic(true),
            // "_instanceof" configuration
            'app.instanceof_task' => (new Definition(DummyTask::class))
                ->setAutoconfigured(true)
                ->setInstanceofConditionals([TaskInterface::class => (new ChildDefinition(''))->setPublic(true)]),
            // #[Autoconfigure(public: true)]
            PublicTask::class => (new Definition(PublicTask::class))->setAutoconfigured(true),
        ]);

        foreach (['app.public_task', 'app.instanceof_task', PublicTask::class] as $id) {
            self::assertFalse($container->hasAlias($id), $id);
            self::assertTrue($container->getDefinition($id)->isPublic(), $id);
        }
    }

    public function testOtherServicesAreIgnored(): void
    {
        $container = $this->compile([
            'app.not_autoconfigured_task' => new Definition(DummyTask::class),
            'app.private_task' => (new Definition(DummyTask::class))->setAutoconfigured(true)->setPublic(false),
            'app.abstract_task' => (new Definition(DummyTask::class))->setAutoconfigured(true)->setAbstract(true),
            'app.transformer' => (new Definition(TrimTransformer::class))->setAutoconfigured(true),
            'app.unknown_class' => (new Definition('App\Unknown'))->setAutoconfigured(true),
        ]);

        foreach (['app.not_autoconfigured_task', 'app.private_task', 'app.abstract_task', 'app.transformer', 'app.unknown_class'] as $id) {
            self::assertFalse($container->hasAlias($id), $id);
        }
        self::assertFalse($container->getDefinition('app.private_task')->isPublic());
        // The tasks of the bundle keep their own tag
        self::assertSame(
            [['package' => 'cleverage/process-bundle', 'version' => '5.4']],
            $container->getDefinition('.container.private.'.DummyTask::class)->getTag('container.private'),
        );
    }

    /**
     * Same order as the compilation of the container (PassConfig).
     *
     * @param array<string, Definition> $definitions
     */
    private function compile(array $definitions): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);
        $container->addDefinitions($definitions);

        (new PublicTaskDeprecationCompilerPass())->process($container);
        (new ResolveClassPass())->process($container);
        (new RegisterAutoconfigureAttributesPass())->process($container);
        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);
        (new AliasDeprecatedPublicServicesPass())->process($container);

        return $container;
    }
}
