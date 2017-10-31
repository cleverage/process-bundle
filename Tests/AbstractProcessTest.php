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
use CleverAge\ProcessBundle\Model\ProcessState;
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
     * Assert that an array of values match what's been registered in the standard queue
     * It can also match task codes using the checkTask flag
     *
     * @param array  $expected
     * @param string $processName
     * @param bool   $checkTask
     */
    protected function assertDataQueue(array $expected, string $processName, bool $checkTask = true)
    {
        $dataQueueListener = $this->container->get('cleverage_process.event_listener.data_queue');
        $actualQueue = $dataQueueListener->getQueue($processName);

        /**
         * @var int          $key
         * @var ProcessState $value
         */
        foreach ($actualQueue as $key => $value) {
            self::assertArrayHasKey($key, $expected);
            if ($checkTask) {
                if (array_key_exists('task', $expected[$key])) {
                    self::assertEquals($expected[$key]['task'], $value->getPreviousState()->getTaskConfiguration()->getCode(), "Task #{$key} does not match");
                }
                if (array_key_exists('value', $expected[$key])) {
                    self::assertEquals($expected[$key]['value'], $value->getInput(), "Value #{$key} does not match");
                }
            } else {
                self::assertEquals($expected[$key], $value->getInput(), "Value #{$key} does not match");

            }
        }
    }
}
