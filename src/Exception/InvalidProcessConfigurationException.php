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

namespace CleverAge\ProcessBundle\Exception;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;

/**
 * Thrown when the process configuration cannot be resolved.
 */
class InvalidProcessConfigurationException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param list<string> $mainTaskList
     */
    public static function createNotInMain(
        ProcessConfiguration $processConfiguration,
        TaskConfiguration $taskConfig,
        array $mainTaskList,
    ): self {
        $taskListStr = '['.implode(', ', $mainTaskList).']';

        return new self(
            "Task '{$taskConfig->getCode()}' is not in main task list : {$taskListStr} (from process: {$processConfiguration->getCode()})"
        );
    }

    public static function createEntryPointHasAncestors(
        ProcessConfiguration $processConfiguration,
        TaskConfiguration $taskConfig,
    ): self {
        return new self(
            "The entry-point '{$taskConfig->getCode()}' cannot have an ancestor (from process: {$processConfiguration->getCode()})"
        );
    }

    /**
     * A task code referenced by the "outputs" or "error_outputs" of a task, or by the "entry_point" or "end_point" of
     * the process, is not a task of the process.
     */
    public static function createUnknownTaskReference(
        string $processCode,
        string $key,
        string $unknownTaskCode,
        ?string $taskCode = null,
    ): self {
        $holder = null === $taskCode ? "the '{$key}'" : "the '{$key}' of the task '{$taskCode}'";

        return new self("Unknown task '{$unknownTaskCode}' in {$holder} (from process: {$processCode})");
    }
}
