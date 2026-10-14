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

namespace CleverAge\ProcessBundle\Tests\Configuration;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskConfiguration::class)]
class TaskConfigurationTest extends TestCase
{
    public function testGetErrorsIsDeprecated(): void
    {
        $taskConfiguration = new TaskConfiguration('task', 'service', [], errorOutputs: ['error']);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $errors = $taskConfiguration->getErrors();
        } finally {
            restore_error_handler();
        }

        self::assertSame(['error'], $errors);
        // PHP 8.4+ also triggers its own deprecation for the #[\Deprecated] attribute
        self::assertContains(
            'Since cleverage/process-bundle 2.0.4: The "CleverAge\ProcessBundle\Configuration\TaskConfiguration::getErrors()" method is deprecated, use "getErrorOutputs()" instead.',
            $deprecations
        );
    }
}
