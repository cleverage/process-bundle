<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
                    self::assertEquals($expected[$key]['task'], $value->getPreviousState()->getTaskConfiguration()->getCode());
                }
                if (array_key_exists('value', $expected[$key])) {
                    self::assertEquals($expected[$key]['value'], $value->getInput());
                }
            } else {
                self::assertEquals($expected[$key], $value->getInput());

            }
        }
    }
}
