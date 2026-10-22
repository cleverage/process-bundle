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

namespace CleverAge\ProcessBundle\Manager;

use CleverAge\ProcessBundle\Exception\MissingProcessException;
use CleverAge\ProcessBundle\Exception\ProcessFailedException;

/**
 * Executes processes (implemented by ProcessManager, autowired by this interface).
 */
interface ProcessManagerInterface
{
    /**
     * Executes the process with the given input (given to its entry point) and context, returns the last output of its
     * end point (null without end point).
     *
     * @param array<string, mixed> $context
     *
     * @throws MissingProcessException when the process does not exist
     * @throws ProcessFailedException  when a task fails with the "stop" error strategy
     */
    public function execute(string $processCode, mixed $input = null, array $context = []): mixed;
}
