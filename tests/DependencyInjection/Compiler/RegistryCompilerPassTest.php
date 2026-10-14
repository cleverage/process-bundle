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
    public function testIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            new RegistryCompilerPass('registry', 'tag', 'append');
        } finally {
            restore_error_handler();
        }

        self::assertSame(['Since cleverage/process-bundle 5.4: The "'.RegistryCompilerPass::class.'" class is deprecated, it will be removed in v6.0.'], $deprecations);
    }

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
