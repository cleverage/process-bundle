<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Tests;


use CleverAge\ProcessBundle\Manager\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide all necessary setup to test a process
 */
abstract class AbstractProcessTest extends KernelTestCase
{
    /** @var  ContainerInterface */
    protected $container;

    /** @var ProcessManager */
    protected $processManager;

    /**
     * Initialize DI
     */
    protected function setUp()
    {
        $kernel = static::bootKernel([
            'environment' => 'test',
            'debug'       => true,
        ]);

        $this->container = $kernel->getContainer();
        $this->processManager = $this->container->get('cleverage_process.manager.process');
    }

    /**
     * @param array $expected
     */
    protected function assertDataQueue($expected)
    {
        $dataQueueListener = $this->container->get('cleverage_process.event_listener.data_queue');
        $actualQueue = $dataQueueListener->getQueue();

        foreach ($actualQueue as $key => $value) {
            self::assertArrayHasKey($key, $expected);
            self::assertEquals($expected[$key], $value);
        }
    }
}