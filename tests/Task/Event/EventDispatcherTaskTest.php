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

namespace CleverAge\ProcessBundle\Tests\Task\Event;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\Event\EventDispatcherTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(EventDispatcherTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(EventDispatcherTaskEvent::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class EventDispatcherTaskTest extends TestCase
{
    /** @var list<string> */
    private array $calledListeners = [];

    /** @var list<string> */
    private array $deprecations = [];

    public function testEventIsDispatchedUnderEventName(): void
    {
        $dispatcher = $this->createDispatcher(['myapp.myevent']);

        $this->execute($dispatcher, ['event_name' => 'myapp.myevent']);

        self::assertSame(['myapp.myevent'], $this->calledListeners);
        self::assertSame([], $this->deprecations);
    }

    public function testEventIsDispatchedUnderClassNameWithoutEventName(): void
    {
        $dispatcher = $this->createDispatcher(['myapp.myevent', EventDispatcherTaskEvent::class]);

        $this->execute($dispatcher, []);

        self::assertSame([EventDispatcherTaskEvent::class], $this->calledListeners);
        self::assertSame([], $this->deprecations);
    }

    public function testEventIsDispatchedOnceWhenEventNameIsTheClassName(): void
    {
        $dispatcher = $this->createDispatcher([EventDispatcherTaskEvent::class]);

        $this->execute($dispatcher, ['event_name' => EventDispatcherTaskEvent::class]);

        self::assertSame([EventDispatcherTaskEvent::class], $this->calledListeners);
        self::assertSame([], $this->deprecations);
    }

    public function testClassNameListenersAreStillCalledWithDeprecation(): void
    {
        $dispatcher = $this->createDispatcher(['myapp.myevent', EventDispatcherTaskEvent::class]);

        $this->execute($dispatcher, ['event_name' => 'myapp.myevent']);

        self::assertSame(['myapp.myevent', EventDispatcherTaskEvent::class], $this->calledListeners);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('listen to "myapp.myevent" instead', $this->deprecations[0]);
    }

    public function testPassiveTaskOutputsInput(): void
    {
        $state = $this->execute($this->createDispatcher([]), [], 'input');

        self::assertSame('input', $state->getOutput());
    }

    public function testInvalidEventNameThrows(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->execute($this->createDispatcher([]), ['event_name' => 123]);
    }

    /**
     * @param list<string> $eventNames
     */
    private function createDispatcher(array $eventNames): EventDispatcher
    {
        $dispatcher = new EventDispatcher();
        foreach ($eventNames as $eventName) {
            $dispatcher->addListener($eventName, function (EventDispatcherTaskEvent $event) use ($eventName): void {
                $this->calledListeners[] = $eventName;
            });
        }

        return $dispatcher;
    }

    private function execute(EventDispatcher $dispatcher, array $options, mixed $input = null): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('dispatch', EventDispatcherTask::class, $options));
        $state->setInput($input);

        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $task = new EventDispatcherTask($dispatcher);
            $task->initialize($state);
            $task->execute($state);
        } finally {
            restore_error_handler();
        }

        return $state;
    }
}
