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

namespace CleverAge\ProcessBundle\Tests\Event;

use CleverAge\ProcessBundle\Event\ConsoleProcessEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

#[CoversClass(ConsoleProcessEvent::class)]
class ConsoleProcessEventTest extends TestCase
{
    public function testGetters(): void
    {
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $event = new ConsoleProcessEvent($input, $output, 'data', ['key' => 'value']);

        self::assertSame($input, $event->getConsoleInput());
        self::assertSame($output, $event->getConsoleOutput());
        self::assertSame('data', $event->getProcessInput());
        self::assertSame(['key' => 'value'], $event->getProcessContext());
    }
}
