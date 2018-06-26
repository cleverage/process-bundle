<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

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

    /**
     * @param LoggerInterface              $logger
     * @param ProcessManager               $processManager
     * @param ProcessConfigurationRegistry $processRegistry
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessManager $processManager,
        ProcessConfigurationRegistry $processRegistry
    ) {
        parent::__construct($logger);
        $this->processManager = $processManager;
        $this->processRegistry = $processRegistry;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        $consoleOutput = $state->getConsoleOutput();

        $processCode = $this->getOption($state, 'process');
        try {
            $output = $this->processManager->execute(
                $this->getOption($state, 'process'),
                $consoleOutput,
                $input,
                $this->getOption($state, 'context')
            );
            $state->setOutput($output);
        } catch (\Throwable $e) {
            $state->setError($input);
            $message = $e->getPrevious() ? $e->getPrevious()->getMessage() : $e->getMessage();
            $logContext = $state->getLogContext();
            $logContext['exception'] = $e;
            $this->logger->error("Process '{$processCode}' has failed: {$message}", $logContext);
            if ($this->getOption($state, self::ERROR_STRATEGY) === self::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($this->getOption($state, self::ERROR_STRATEGY) === self::STRATEGY_STOP) {
                $state->stop($e);
            }
        }
    }

    /**
     * {@inheritdoc}
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
        parent::configureOptions($resolver);

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
