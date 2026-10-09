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

use CleverAge\ProcessBundle\DependencyInjection\Compiler\RegistryCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

#[\PHPUnit\Framework\Attributes\CoversClass(RegistryCompilerPass::class)]
class RegistryCompilerPassTest extends TestCase
{
    public function testTaggedServicesAreAddedToTheRegistry(): void
    {
        $container = new ContainerBuilder();
        $container->register('registry', \ArrayObject::class);
        $container->register('service', \stdClass::class)->addTag('tag');

        (new RegistryCompilerPass('registry', 'tag', 'append'))->process($container);

        self::assertEquals([['append', [new Reference('service')]]], $container->getDefinition('registry')->getMethodCalls());
    }

    public function testMissingRegistryIsIgnored(): void
    {
        $container = new ContainerBuilder();
        $container->register('service', \stdClass::class)->addTag('tag');

        (new RegistryCompilerPass('registry', 'tag', 'append'))->process($container);

        self::assertFalse($container->has('registry'));
    }

    public function testUndefinedConfigurationThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The registry, tag and method of the RegistryCompilerPass must be defined');

        (new RegistryCompilerPass())->process(new ContainerBuilder());
    }
}
