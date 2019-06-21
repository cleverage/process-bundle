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

use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CleverAge\ProcessBundle\EventListener\DataQueueEventListener;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provide all necessary setup to test a process
 */
abstract class AbstractProcessTest extends KernelTestCase
{
    /** @var ProcessManager */
    protected $processManager;

    /** @var ProcessConfigurationRegistry */
    protected $processConfigurationRegistry;

    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /**
     * Initialize DI
     */
    protected function setUp()
    {
        static::bootKernel();

        $this->processManager = $this->getContainer()->get(ProcessManager::class);
        $this->processConfigurationRegistry = $this->getContainer()->get(ProcessConfigurationRegistry::class);
        $this->transformerRegistry = $this->getContainer()->get(TransformerRegistry::class);
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
        $dataQueueListener = $this->getContainer()->get(DataQueueEventListener::class);
        $actualQueue = $dataQueueListener->getQueue($processName);

        self::assertCount(\count($expected), $actualQueue, 'Event count does not match');

        /**
         * @var int          $key
         * @var ProcessState $value
         */
        foreach ($actualQueue as $key => $value) {
            self::assertArrayHasKey($key, $expected);
            if ($checkTask) {
                if (array_key_exists('task', $expected[$key])) {
                    self::assertEquals(
                        $expected[$key]['task'],
                        $value->getPreviousState()->getTaskConfiguration()->getCode(),
                        "Task #{$key} does not match"
                    );
                }
                if (array_key_exists('value', $expected[$key])) {
                    self::assertEquals($expected[$key]['value'], $value->getInput(), "Value #{$key} does not match");
                }
            } else {
                self::assertEquals($expected[$key], $value->getInput(), "Value #{$key} does not match");
            }
        }
    }

    /**
     * Returns the booted symfony container
     *
     * Compatibility backport for symfony/phpunit-bridge that should work with v3 or v4
     *
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        if(isset(self::$container)) {
            return self::$container;
        }

        $container = self::$kernel->getContainer();
        $container = $container->has('test.service_container') ? $container->get('test.service_container') : $container;

        return $container;
    }

    /**
     * Helper method to configure options and test a transformation
     *
     * @param string $transformerCode
     * @param mixed  $expected
     * @param mixed  $actual
     * @param array  $options
     *
     * @throws ExceptionInterface
     */
    protected function assertTransformation(string $transformerCode, $expected, $actual, array $options = [])
    {
        $transformer = $this->transformerRegistry->getTransformer($transformerCode);

        if ($transformer instanceof ConfigurableTransformerInterface) {
            $resolver = new OptionsResolver();
            $transformer->configureOptions($resolver);
            $options = $resolver->resolve($options);
        }

        self::assertEquals($expected, $transformer->transform($actual, $options));
    }
}
