<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Event\ProcessEvent;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\MethodProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;

class ProcessManagerTest extends AbstractProcessTest
{

    public function testProcessEvents()
    {
        $edProphecy = $this->prophesize(EventDispatcherInterface::class);

        $dispatchStartProphecy = new MethodProphecy($edProphecy, 'dispatch', [new TypeToken(ProcessEvent::class), ProcessEvent::EVENT_PROCESS_STARTED]);
        $dispatchStartProphecy->shouldBeCalled();
        $edProphecy->addMethodProphecy($dispatchStartProphecy);

        $dispatchStartProphecy = new MethodProphecy($edProphecy, 'dispatch', [new TypeToken(ProcessEvent::class), ProcessEvent::EVENT_PROCESS_ENDED]);
        $dispatchStartProphecy->shouldBeCalled();
        $edProphecy->addMethodProphecy($dispatchStartProphecy);

        $dispatchStartProphecy = new MethodProphecy($edProphecy, 'dispatch', [new TypeToken(ProcessEvent::class), ProcessEvent::EVENT_PROCESS_FAILED]);
        $dispatchStartProphecy->shouldNotBeCalled();
        $edProphecy->addMethodProphecy($dispatchStartProphecy);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $edProphecy->reveal();
        $processManager = new ProcessManager(
            $this->getContainer(),
            $this->getContainer()->get(ProcessLogger::class),
            $this->getContainer()->get(TaskLogger::class),
            $this->getContainer()->get(ProcessConfigurationRegistry::class),
            $this->getContainer()->get(ContextualOptionResolver::class),
            $eventDispatcher
        );

        $processManager->execute('test.simple_process');
    }
}
