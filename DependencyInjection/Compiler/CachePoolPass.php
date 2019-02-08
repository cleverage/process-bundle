<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\DependencyInjection\Compiler;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Generic compiler pass to add tagged services to a registry
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CachePoolPass implements CompilerPassInterface
{
    /**
     * Inject tagged services into defined registry
     *
     * @api
     *
     * @param ContainerBuilder $container
     *
     * @throws InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $name = 'cache.app.cleverage_process';
        $pool = [
            'adapter' => 'cache.app',
            'public' => true,
        ];
        $definition = new ChildDefinition($pool['adapter']);
        $container->registerAliasForArgument($name, CacheInterface::class);
        $container->registerAliasForArgument($name, CacheItemPoolInterface::class);
        $definition->setPublic($pool['public']);

        $definition->addTag('cache.pool');
        $container->setDefinition($name, $definition);
    }
}
