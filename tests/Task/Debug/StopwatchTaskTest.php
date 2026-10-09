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

namespace CleverAge\ProcessBundle\Tests\Task\Debug;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Debug\StopwatchTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

#[\PHPUnit\Framework\Attributes\CoversClass(StopwatchTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class StopwatchTaskTest extends TestCase
{
    public function testEveryRootSectionEventIsLoggedAtInfoLevel(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('first', 'cat1');
        $stopwatch->stop('first');
        $stopwatch->start('second', 'cat2');
        $logger = $this->createLogger();

        $state = $this->execute($logger, $stopwatch, 'input');

        self::assertCount(2, $logger->records);
        self::assertSame([LogLevel::INFO, LogLevel::INFO], array_column($logger->records, 'level'));
        self::assertSame($stopwatch->getEvent('first'), $logger->records[0]['message']);
        self::assertSame($stopwatch->getEvent('second'), $logger->records[1]['message']);
        self::assertStringStartsWith('cat1/first: ', (string) $logger->records[0]['message']);
        self::assertStringStartsWith('cat2/second: ', (string) $logger->records[1]['message']);
        self::assertNull($state->getOutput());
        self::assertFalse($state->isSkipped());
    }

    public function testEventsOfOtherSectionsAreNotLogged(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->openSection();
        $stopwatch->start('nested');
        $stopwatch->stop('nested');
        $stopwatch->stopSection('section');
        $logger = $this->createLogger();

        $this->execute($logger, $stopwatch, null);

        self::assertNotSame([], $stopwatch->getSectionEvents('section'));
        foreach ($logger->records as $record) {
            self::assertInstanceOf(StopwatchEvent::class, $record['message']);
            self::assertStringNotContainsString('nested', (string) $record['message']);
        }
    }

    public function testNothingIsLoggedWithoutEvent(): void
    {
        $logger = $this->createLogger();

        $state = $this->execute($logger, new Stopwatch(), 'input');

        self::assertSame([], $logger->records);
        self::assertNull($state->getOutput());
    }

    private function execute(AbstractLogger $logger, Stopwatch $stopwatch, mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->reset(false);
        $state->setInput($input);

        (new StopwatchTask($logger, $stopwatch))->execute($state);

        return $state;
    }

    /**
     * @return AbstractLogger&object{records: list<array{level: mixed, message: string|\Stringable, context: array<mixed>}>}
     */
    private function createLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            /** @var list<array{level: mixed, message: string|\Stringable, context: array<mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
    }
}
