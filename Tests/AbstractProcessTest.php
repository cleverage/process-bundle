<?php

namespace CleverAge\ProcessBundle\Tests;


use CleverAge\ProcessBundle\Manager\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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