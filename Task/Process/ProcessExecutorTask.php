<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Process;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Execute one or many processes while chaining inputs in a iterable way
 */
class ProcessExecutorTask extends AbstractConfigurableTask
{
    /** @var ProcessManager */
    protected $processManager;

    /** @var ProcessConfigurationRegistry */
    protected $processRegistry;

    /** @var array */
    protected $process;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ProcessManager               $processManager
     * @param ProcessConfigurationRegistry $processRegistry
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ProcessManager $processManager,
        ProcessConfigurationRegistry $processRegistry,
        LoggerInterface $logger
    ) {
        $this->processManager = $processManager;
        $this->processRegistry = $processRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Exception
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        $process = $this->getOption($state, 'process');

        $output = $this->processManager->execute(
            $process,
            $input,
            $this->getOption($state, 'context')
        );
        $state->setOutput($output);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function initialize(ProcessState $state)
    {
        parent::initialize($state);
        $this->process = $this->getOption($state, 'process');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Form\Exception\InvalidConfigurationException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('process');
        $resolver->setDefaults(
            [
                'context' => [],
            ]
        );
        $resolver->addAllowedTypes('process', 'string');
        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setNormalizer(
            'process',
            function (Options $options, $processCode) {
                if (!$this->processRegistry->hasProcessConfiguration($processCode)) {
                    throw new InvalidConfigurationException("Unknown process {$processCode}");
                }

                return $processCode;
            }
        );
    }
}
