<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Generic compiler pass to add tagged services to a registry
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class RegistryCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $registry;

    /** @var string */
    protected $tag;

    /** @var string */
    protected $method;

    /**
     * @param string $registry
     * @param string $tag
     * @param string $method
     */
    public function __construct($registry, $tag, $method)
    {
        $this->registry = $registry;
        $this->tag = $tag;
        $this->method = $method;
    }

    /**
     * Inject tagged services into defined registry
     *
     * @param ContainerBuilder $container
     *
     * @throws InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @api
     *
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has($this->registry)) {
            return;
        }

        $definition = $container->findDefinition($this->registry);
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                $this->method,
                [new Reference($id)]
            );
        }
    }
}
