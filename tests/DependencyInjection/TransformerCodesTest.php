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
use CleverAge\ProcessBundle\Transformer\GenericTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configuration check: the code of the "cleverage.transformer" tag of every transformer of the bundle (lazy loading)
 * must be the one returned by its getCode() method. It runs the code of every transformer, which it does not cover.
 */
#[CoversNothing]
class TransformerCodesTest extends TestCase
{
    public function testEveryTransformerIsRegisteredWithItsCode(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([[
            'generic_transformers' => ['app_generic' => ['transformers' => ['trim' => []]]],
        ]], $container);
        // The class of the services whose id is the class name, as when the container is compiled
        (new ResolveClassPass())->process($container);

        $tagged = $container->findTaggedServiceIds('cleverage.transformer');
        self::assertCount(38, $tagged);
        foreach ($tagged as $id => $tags) {
            $class = $container->getDefinition($id)->getClass();
            self::assertIsString($class);
            if (GenericTransformer::class === $class) {
                $code = 'app_generic';
            } else {
                self::assertTrue(is_a($class, TransformerInterface::class, true), $class);
                $transformer = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
                self::assertInstanceOf(TransformerInterface::class, $transformer);
                $code = $transformer->getCode();
            }
            // Lazy loading: the code of the tag must be the one returned by getCode()
            self::assertSame([['code' => $code]], $tags, $id);
        }
    }
}
