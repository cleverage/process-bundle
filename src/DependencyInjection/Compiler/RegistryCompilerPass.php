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

namespace CleverAge\ProcessBundle\DependencyInjection\Compiler;

use CleverAge\ProcessBundle\Exception\LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Generic compiler pass to add tagged services to a registry.
 *
 * @deprecated since 5.4, no longer used by the bundle (the transformers are registered by
 *             TransformerLocatorCompilerPass), will be removed in v6.0
 */
class RegistryCompilerPass implements CompilerPassInterface
{
    public function __construct(
        protected ?string $registry = null,
        protected ?string $tag = null,
        protected ?string $method = null,
    ) {
        trigger_deprecation('cleverage/process-bundle', '5.4', 'The "%s" class is deprecated, it will be removed in v6.0.', self::class);
    }

    /**
     * Inject tagged services into defined registry.
     */
    public function process(ContainerBuilder $container): void
    {
        if (\in_array(null, [$this->registry, $this->tag, $this->method], true)) {
            throw new LogicException('The registry, tag and method of the RegistryCompilerPass must be defined');
        }

        if (!$container->has($this->registry)) {
            return;
        }

        $definition = $container->findDefinition($this->registry);
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall($this->method, [new Reference($id)]);
        }
    }
}
