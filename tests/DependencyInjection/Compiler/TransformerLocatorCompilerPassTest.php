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

use CleverAge\ProcessBundle\DependencyInjection\Compiler\TransformerLocatorCompilerPass;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(TransformerLocatorCompilerPass::class)]
class TransformerLocatorCompilerPassTest extends TestCase
{
    public function testTransformersWithACodeAreInTheLocatorTheOthersAreAddedEagerly(): void
    {
        $container = $this->createContainer();
        $container->register('app.coded', \stdClass::class)->addTag('cleverage.transformer', ['code' => 'coded']);
        // Autoconfiguration on TransformerInterface adds a tag without code next to the one of #[AsTransformer]
        $container->register('app.attributed', \stdClass::class)
            ->addTag('cleverage.transformer')
            ->addTag('cleverage.transformer', ['code' => 'attributed']);
        $container->register('app.legacy', \stdClass::class)->addTag('cleverage.transformer');

        (new TransformerLocatorCompilerPass())->process($container);

        $registry = $container->getDefinition('cleverage_process.registry.transformer');
        $locatorReference = $registry->getArgument(0);
        self::assertInstanceOf(Reference::class, $locatorReference);
        $services = $container->getDefinition((string) $locatorReference)->getArgument(0);
        self::assertIsArray($services);
        self::assertSame(['coded', 'attributed'], array_keys($services));
        self::assertInstanceOf(ServiceClosureArgument::class, $services['coded']);
        self::assertSame('app.coded', (string) $services['coded']->getValues()[0]);
        // Deprecated: eager registration
        self::assertEquals([['addTransformer', [new Reference('app.legacy')]]], $registry->getMethodCalls());
    }

    public function testDuplicateCodeThrows(): void
    {
        $container = $this->createContainer();
        $container->register('app.first', \stdClass::class)->addTag('cleverage.transformer', ['code' => 'same']);
        $container->register('app.second', \stdClass::class)->addTag('cleverage.transformer', ['code' => 'same']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The transformer code "same" is used by the "app.first" and "app.second" services.');

        (new TransformerLocatorCompilerPass())->process($container);
    }

    public function testInvalidCodeThrows(): void
    {
        $container = $this->createContainer();
        $container->register('app.invalid', \stdClass::class)->addTag('cleverage.transformer', ['code' => '']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "code" attribute of the "cleverage.transformer" tag of the "app.invalid" service must be a non-empty string.');

        (new TransformerLocatorCompilerPass())->process($container);
    }

    public function testNothingIsDoneWithoutTheRegistry(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.coded', \stdClass::class)->addTag('cleverage.transformer', ['code' => 'coded']);

        (new TransformerLocatorCompilerPass())->process($container);

        self::assertFalse($container->hasDefinition('cleverage_process.registry.transformer'));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('cleverage_process.registry.transformer', TransformerRegistry::class)->setArguments([null]);

        return $container;
    }
}
