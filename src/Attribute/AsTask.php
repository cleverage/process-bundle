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

namespace CleverAge\ProcessBundle\Attribute;

/**
 * Gives a code to a task service, to reference it with the `task` key of a task configuration (`task: <code>`)
 * instead of its service id or class name (`service: '@<id>'`).
 *
 * Requires autoconfiguration; equivalent to a `cleverage.task` tag with a `code` attribute. The code is unique: two task
 * services with the same code fail the container compilation (use a prefix for the application tasks).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsTask
{
    public function __construct(
        public string $code,
    ) {
    }
}
