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

use CleverAge\ProcessBundle\Task\Debug\StopwatchTask;
use CleverAge\ProcessBundle\Task\Process\CommandRunnerTask;
use CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask;
use CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\DeserializerTask;
use CleverAge\ProcessBundle\Task\Serialization\NormalizerTask;
use CleverAge\ProcessBundle\Task\Serialization\SerializerTask;
use CleverAge\ProcessBundle\Task\UnavailableTask;
use CleverAge\ProcessBundle\Task\Validation\ValidatorTask;
use CleverAge\ProcessBundle\Transformer\Serialization\DenormalizeTransformer;
use CleverAge\ProcessBundle\Transformer\Serialization\NormalizeTransformer;
use CleverAge\ProcessBundle\Transformer\String\SlugifyTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use CleverAge\ProcessBundle\Transformer\UnavailableTransformer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Replaces the tasks and transformers whose dependencies are not available by services failing with an explicit
 * message when they are used, instead of failing the container compilation (autowiring) for every application.
 *
 * @internal
 */
final readonly class UnavailableServicesCompilerPass implements CompilerPassInterface
{
    public const SERIALIZER_MESSAGE = 'The "%s" service requires the Symfony serializer, which does not seem enabled: consider enabling framework.serializer (see https://symfony.com/doc/current/reference/configuration/framework.html#reference-serializer-enabled).';

    public const VALIDATOR_MESSAGE = 'The "%s" service requires the Symfony validator, which does not seem enabled: consider enabling framework.validation (see https://symfony.com/doc/current/reference/configuration/framework.html#reference-validation-enabled).';

    public const STOPWATCH_MESSAGE = 'The "%s" service requires the Symfony Stopwatch component, which does not seem installed: try "composer require symfony/stopwatch".';

    public const PROCESS_MESSAGE = 'The "%s" service requires the Symfony Process component, which does not seem installed: try "composer require symfony/process".';

    public const INTL_MESSAGE = 'The "%s" service requires the PHP intl extension, which does not seem installed (see https://www.php.net/manual/en/book.intl.php).';

    /**
     * @param (\Closure(class-string): bool)|null $classExists defaults to class_exists() (the tests check the missing packages)
     */
    public function __construct(
        private ?\Closure $classExists = null,
    ) {
    }

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

        // The debug.stopwatch service is registered by the FrameworkBundle when symfony/stopwatch is installed
        if (!$container->has('debug.stopwatch')) {
            $this->replace($container, self::STOPWATCH_MESSAGE, [StopwatchTask::class]);
        }

        if (!($this->classExists ?? class_exists(...))(Process::class)) {
            $this->replace($container, self::PROCESS_MESSAGE, [CommandRunnerTask::class, ProcessLauncherTask::class]);
        }

        if (!($this->classExists ?? class_exists(...))(\Transliterator::class)) {
            $this->replace($container, self::INTL_MESSAGE, [SlugifyTransformer::class]);
        }
    }

    /**
     * @param list<class-string> $classes the services are registered with their class name as id (or an alias)
     */
    private function replace(ContainerBuilder $container, string $message, array $classes): void
    {
        foreach ($classes as $class) {
            $id = $container->hasAlias($class) ? (string) $container->getAlias($class) : $class;
            if (!$container->hasDefinition($id)) {
                continue;
            }
            $original = $container->getDefinition($id);
            $text = \sprintf($message, $class);
            if (is_a($class, TransformerInterface::class, true)) {
                // The code is needed by the transformer registry: the one of the tag (see TransformerLocatorCompilerPass),
                // or, for a definition tagged without code (deprecated), the one returned by the bundle transformer
                $code = $this->getTagCode($original)
                    ?? (new \ReflectionClass($class))->newInstanceWithoutConstructor()->getCode();
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

    private function getTagCode(Definition $definition): ?string
    {
        foreach ($definition->getTag('cleverage.transformer') as $attributes) {
            if (isset($attributes['code']) && \is_string($attributes['code'])) {
                return $attributes['code'];
            }
        }

        return null;
    }
}
