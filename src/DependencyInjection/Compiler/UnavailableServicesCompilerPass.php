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

use CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\DeserializerTask;
use CleverAge\ProcessBundle\Task\Serialization\NormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\SerializerTask;
use CleverAge\ProcessBundle\Task\UnavailableTask;
use CleverAge\ProcessBundle\Task\Validation\ValidatorTask;
use CleverAge\ProcessBundle\Transformer\Serialization\DenormalizeTransformer;
use CleverAge\ProcessBundle\Transformer\Serialization\NormalizeTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use CleverAge\ProcessBundle\Transformer\UnavailableTransformer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Replaces the tasks and transformers whose dependencies are not available by services failing with an explicit
 * message when they are used, instead of failing the container compilation (autowiring) for every application.
 *
 * @internal
 */
final class UnavailableServicesCompilerPass implements CompilerPassInterface
{
    public const SERIALIZER_MESSAGE = 'The "%s" service requires the Symfony serializer, which does not seem enabled: consider enabling framework.serializer (see https://symfony.com/doc/current/reference/configuration/framework.html#reference-serializer-enabled).';

    public const VALIDATOR_MESSAGE = 'The "%s" service requires the Symfony validator, which does not seem enabled: consider enabling framework.validation (see https://symfony.com/doc/current/reference/configuration/framework.html#reference-validation-enabled).';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('serializer') && !$container->has(DenormalizerInterface::class)) {
            $this->replace($container, self::SERIALIZER_MESSAGE, [
                NormalizerTask::class,
                DenormalizerTask::class,
                SerializerTask::class,
                DeserializerTask::class,
                NormalizeTransformer::class,
                DenormalizeTransformer::class,
            ]);
        }

        if (!$container->has('validator') && !$container->has(ValidatorInterface::class)) {
            $this->replace($container, self::VALIDATOR_MESSAGE, [ValidatorTask::class]);
        }
    }

    /**
     * @param list<class-string> $ids
     */
    private function replace(ContainerBuilder $container, string $message, array $ids): void
    {
        foreach ($ids as $id) {
            if (!$container->hasDefinition($id)) {
                continue;
            }
            $original = $container->getDefinition($id);
            $text = \sprintf($message, $id);
            if (is_a($id, TransformerInterface::class, true)) {
                // The code is needed by the transformer registry: the bundle transformers return a constant
                $code = (new \ReflectionClass($id))->newInstanceWithoutConstructor()->getCode();
                $definition = new Definition(UnavailableTransformer::class, [$code, $text]);
            } else {
                $definition = new Definition(UnavailableTask::class, [$text]);
                $definition->setShared(false);
            }
            $definition->setPublic($original->isPublic());
            $definition->setTags($original->getTags());
            $container->setDefinition($id, $definition);
        }
    }
}
