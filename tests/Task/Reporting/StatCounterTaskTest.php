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

namespace CleverAge\ProcessBundle\Tests\Task\Reporting;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Reporting\StatCounterTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

#[\PHPUnit\Framework\Attributes\CoversClass(StatCounterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class StatCounterTaskTest extends TestCase
{
    public function testInputIsPassedToTheOutputAndCountIsLoggedOnFinalize(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = (string) $message;
            }
        };
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $task = new StatCounterTask($logger);

        $outputs = [];
        foreach (['a', ['b'], null] as $input) {
            $state->reset(false);
            $state->setInput($input);
            $task->execute($state);
            $outputs[] = $state->isSkipped() ? 'skipped' : $state->getOutput();
        }
        $task->finalize($state);

        self::assertSame(['a', ['b'], null], $outputs);
        self::assertSame(['Processed item count: 3'], $logger->messages);
    }
}
