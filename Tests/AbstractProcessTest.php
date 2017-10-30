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
            'environment' => 'test'
        ]);

        $this->container = $kernel->getContainer();
        $this->processManager = $this->container->get('cleverage_process.manager.process');
    }
}