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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Psr\Container\ContainerInterface;

/**
 * Resolves the service of a task configuration (`service` key, with or without a leading `@`).
 *
 * The task services are fetched from a service locator built at compile time from the process configurations
 * (see TaskLocatorCompilerPass). The fallback container is only used, with a deprecation, for services that are not
 * in the locator (e.g. a process configuration added at runtime).
 */
final class TaskRegistry implements TaskRegistryInterface
{
    public function __construct(
        protected ContainerInterface $locator,
        protected ?ContainerInterface $fallbackContainer = null,
    ) {
    }

    public function getTask(TaskConfiguration $taskConfiguration): TaskInterface
    {
        $serviceReference = $taskConfiguration->getServiceReference();
        $id = ltrim($serviceReference, '@');
        if ($this->locator->has($id)) {
            $task = $this->locator->get($id);
        } elseif ($this->fallbackContainer instanceof ContainerInterface && $this->fallbackContainer->has($id)) {
            trigger_deprecation(
                'cleverage/process-bundle',
                '5.4',
                'Fetching the service "%s" of the task "%s" from the service container is deprecated: it is not referenced by the process configurations known when the container is compiled. It will not be found anymore in v6.0.',
                $id,
                $taskConfiguration->getCode(),
            );
            $task = $this->fallbackContainer->get($id);
        } elseif (str_starts_with($serviceReference, '@') && $this->fallbackContainer instanceof ContainerInterface) {
            // Same exception as before the locator for an unknown service referenced with "@"
            $task = $this->fallbackContainer->get($id);
        } else {
            throw new UnexpectedValueException("Unable to resolve service reference for Task '{$taskConfiguration->getCode()}'");
        }
        if (!$task instanceof TaskInterface) {
            throw new UnexpectedValueException("Service defined in Task '{$taskConfiguration->getCode()}' is not a TaskInterface");
        }

        return $task;
    }
}
