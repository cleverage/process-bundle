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

use CleverAge\ProcessBundle\DependencyInjection\Compiler\CheckSerializerCompilerPass;
use CleverAge\ProcessBundle\Tests\IgnoreDeprecationsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

#[\PHPUnit\Framework\Attributes\CoversClass(CheckSerializerCompilerPass::class)]
class CheckSerializerCompilerPassTest extends TestCase
{
    use IgnoreDeprecationsTrait;

    public function testIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            new CheckSerializerCompilerPass();
        } finally {
            restore_error_handler();
        }

        self::assertSame(['Since cleverage/process-bundle 5.4: The "'.CheckSerializerCompilerPass::class.'" class is deprecated, it will be removed in v6.0.'], $deprecations);
    }

    public function testMissingSerializerFails(): void
    {
        $this->expectException(AutowiringFailedException::class);

        self::ignoreDeprecations(static fn (): CheckSerializerCompilerPass => new CheckSerializerCompilerPass())->process(new ContainerBuilder());
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testSerializerIsFound(): void
    {
        $container = new ContainerBuilder();
        $container->register('serializer', \stdClass::class);

        self::ignoreDeprecations(static fn (): CheckSerializerCompilerPass => new CheckSerializerCompilerPass())->process($container);
    }
}
