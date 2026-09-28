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
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Reporting\AdvancedStatCounterTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

#[\PHPUnit\Framework\Attributes\CoversClass(AdvancedStatCounterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class AdvancedStatCounterTaskTest extends TestCase
{
    public function testEveryExecutionIsLoggedWithShowEveryOne(): void
    {
        [$messages, $skipped] = $this->runTask(3, ['show_every' => 1]);

        self::assertSame([false, false, false], $skipped);
        self::assertCount(3, $messages);
        self::assertStringContainsString(' 1 items processed', $messages[0]);
        self::assertStringContainsString(' 2 items processed', $messages[1]);
        self::assertStringContainsString(' 3 items processed', $messages[2]);
    }

    public function testEveryNthExecutionIsLogged(): void
    {
        [$messages, $skipped] = $this->runTask(7, ['show_every' => 3, 'num_items' => 10]);

        self::assertSame([true, true, false, true, true, false, true], $skipped);
        self::assertCount(2, $messages);
        self::assertStringContainsString(' 30 items processed', $messages[0]);
        self::assertStringContainsString(' 60 items processed', $messages[1]);
    }

    public function testSkipFirstExecutionsAreNotCounted(): void
    {
        [$messages, $skipped] = $this->runTask(4, ['show_every' => 2, 'skip_first' => 1]);

        self::assertSame([true, true, false, true], $skipped);
        self::assertCount(1, $messages);
        self::assertStringContainsString(' 2 items processed', $messages[0]);
    }

    /**
     * @param array<string, int> $options
     *
     * @return array{list<string>, list<bool>}
     */
    private function runTask(int $executions, array $options): array
    {
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = (string) $message;
            }
        };

        $state = $this->createState(AdvancedStatCounterTask::class, $options);
        $task = new AdvancedStatCounterTask($logger);
        $task->initialize($state);

        $skipped = [];
        for ($i = 0; $i < $executions; ++$i) {
            $state->reset(false);
            $task->execute($state);
            $skipped[] = $state->isSkipped();
        }

        return [$logger->messages, $skipped];
    }

    private function createState(string $class, array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('task', $class, $options));

        return $state;
    }
}
