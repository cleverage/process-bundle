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
use CleverAge\ProcessBundle\DependencyInjection\Compiler\UnavailableServicesCompilerPass;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\DeserializerTask;
use CleverAge\ProcessBundle\Task\Serialization\NormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\SerializerTask;
use CleverAge\ProcessBundle\Task\UnavailableTask;
use CleverAge\ProcessBundle\Task\Validation\ValidatorTask;
use CleverAge\ProcessBundle\Transformer\Serialization\DenormalizeTransformer;
use CleverAge\ProcessBundle\Transformer\Serialization\NormalizeTransformer;
use CleverAge\ProcessBundle\Transformer\UnavailableTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(UnavailableServicesCompilerPass::class)]
#[UsesClass(CleverAgeProcessExtension::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(NormalizeTransformer::class)]
#[UsesClass(DenormalizeTransformer::class)]
class UnavailableServicesCompilerPassTest extends TestCase
{
    private const SERIALIZER_TASKS = [NormalizerTask::class, DenormalizerTask::class, SerializerTask::class, DeserializerTask::class];

    public function testServicesAreKeptWhenTheirDependenciesAreAvailable(): void
    {
        $container = $this->createContainer(serializer: true, validator: true);

        foreach ([...self::SERIALIZER_TASKS, NormalizeTransformer::class, DenormalizeTransformer::class, ValidatorTask::class] as $id) {
            self::assertSame($id, $container->findDefinition($id)->getClass());
        }
    }

    public function testSerializerServicesAreReplacedWithoutSerializer(): void
    {
        $container = $this->createContainer(serializer: false, validator: true);

        foreach (self::SERIALIZER_TASKS as $id) {
            $definition = $container->findDefinition($id);
            self::assertSame(UnavailableTask::class, $definition->getClass(), $id);
            self::assertSame([\sprintf(UnavailableServicesCompilerPass::SERIALIZER_MESSAGE, $id)], $definition->getArguments());
            // Still referenced by the alias named after the class, and logged in the tasks channel
            self::assertTrue($container->getAlias($id)->isPublic());
            self::assertFalse($definition->isShared());
            self::assertSame([['channel' => 'cleverage_process_task']], $definition->getTag('monolog.logger'));
        }
        foreach ([NormalizeTransformer::class => 'normalize', DenormalizeTransformer::class => 'denormalize'] as $id => $code) {
            $definition = $container->findDefinition($id);
            self::assertSame(UnavailableTransformer::class, $definition->getClass(), $id);
            // Still registered under its code, so that using it gives the explicit message
            self::assertSame([$code, \sprintf(UnavailableServicesCompilerPass::SERIALIZER_MESSAGE, $id)], $definition->getArguments());
            self::assertTrue($definition->hasTag('cleverage.transformer'));
        }
        self::assertSame(ValidatorTask::class, $container->findDefinition(ValidatorTask::class)->getClass());
    }

    public function testTransformerTaggedWithoutCodeKeepsTheCodeOfTheClass(): void
    {
        $container = new ContainerBuilder();
        // e.g. a definition overridden by the application, tagged without code (deprecated)
        $container->register(NormalizeTransformer::class, NormalizeTransformer::class)->addTag('cleverage.transformer');

        (new UnavailableServicesCompilerPass())->process($container);

        $definition = $container->getDefinition(NormalizeTransformer::class);
        self::assertSame(UnavailableTransformer::class, $definition->getClass());
        self::assertSame('normalize', $definition->getArgument(0));
    }

    public function testValidatorTaskIsReplacedWithoutValidator(): void
    {
        $container = $this->createContainer(serializer: true, validator: false);

        $definition = $container->findDefinition(ValidatorTask::class);
        self::assertSame(UnavailableTask::class, $definition->getClass());
        self::assertSame([\sprintf(UnavailableServicesCompilerPass::VALIDATOR_MESSAGE, ValidatorTask::class)], $definition->getArguments());
        self::assertTrue($container->getAlias(ValidatorTask::class)->isPublic());
        self::assertFalse($definition->isShared());
        self::assertSame(NormalizerTask::class, $container->findDefinition(NormalizerTask::class)->getClass());
    }

    private function createContainer(bool $serializer, bool $validator): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);
        if ($serializer) {
            $container->setDefinition('serializer', new Definition(\stdClass::class));
        }
        if ($validator) {
            $container->setDefinition('validator', new Definition(\stdClass::class));
        }
        (new UnavailableServicesCompilerPass())->process($container);

        return $container;
    }
}
