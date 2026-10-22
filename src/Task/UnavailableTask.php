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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Exception\LogicException;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

/**
 * Replaces a task whose dependencies are not available (e.g. the Symfony serializer is not enabled), so that the
 * container still compiles: the task fails only when it is executed, with an explicit message.
 *
 * @internal
 *
 * @see \CleverAge\ProcessBundle\DependencyInjection\Compiler\UnavailableServicesCompilerPass
 */
final readonly class UnavailableTask implements TaskInterface
{
    public function __construct(
        private string $message,
    ) {
    }

    public function execute(ProcessState $state): void
    {
        throw new LogicException($this->message);
    }
}
