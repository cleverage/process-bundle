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
        [$messages, $outputs] = $this->runTask(3, ['show_every' => 1]);

        self::assertSame([0, 1, 2], $outputs);
        self::assertCount(3, $messages);
        self::assertStringContainsString(' 1 items processed', $messages[0]);
        self::assertStringContainsString(' 2 items processed', $messages[1]);
        self::assertStringContainsString(' 3 items processed', $messages[2]);
    }

    public function testEveryNthExecutionIsLogged(): void
    {
        [$messages, $outputs] = $this->runTask(7, ['show_every' => 3, 'num_items' => 10]);

        self::assertSame([0, 1, 2, 3, 4, 5, 6], $outputs);
        self::assertCount(2, $messages);
        self::assertStringContainsString(' 30 items processed', $messages[0]);
        self::assertStringContainsString(' 60 items processed', $messages[1]);
    }

    public function testSkipFirstExecutionsAreNotCounted(): void
    {
        [$messages, $outputs] = $this->runTask(4, ['show_every' => 2, 'skip_first' => 1]);

        self::assertSame([0, 1, 2, 3], $outputs);
        self::assertCount(1, $messages);
        self::assertStringContainsString(' 2 items processed', $messages[0]);
    }

    /**
     * @param array<string, int> $options
     *
     * Execute the task $executions times with the inputs 0, 1, 2...
     *
     * @return array{list<string>, list<mixed>} logged messages, and outputs (null when skipped)
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

        $outputs = [];
        for ($i = 0; $i < $executions; ++$i) {
            $state->reset(false);
            $state->setInput($i);
            $task->execute($state);
            $outputs[] = $state->isSkipped() ? null : $state->getOutput();
        }

        return [$logger->messages, $outputs];
    }

    /**
     * @param array<string, mixed> $options
     */
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
