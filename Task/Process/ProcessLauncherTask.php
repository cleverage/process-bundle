<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Process;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\SubprocessInstance;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Launch a new process for each input received, input must be a scalar, a resource or a \Traversable
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessLauncherTask extends AbstractConfigurableTask implements FlushableTaskInterface, IterableTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ProcessConfigurationRegistry */
    protected $processRegistry;

    /** @var KernelInterface */
    protected $kernel;

    /** @var SubprocessInstance[] */
    protected $launchedProcesses = [];

    /** @var \SplQueue */
    protected $finishedBuffers;

    /** @var bool */
    protected $flushMode = false;

    /**
     * @param LoggerInterface              $logger
     * @param ProcessConfigurationRegistry $processRegistry
     * @param KernelInterface              $kernel
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessConfigurationRegistry $processRegistry,
        KernelInterface $kernel
    ) {
        $this->logger = $logger;
        $this->processRegistry = $processRegistry;
        $this->kernel = $kernel;

        $this->finishedBuffers = new \SplQueue();
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        // TODO still not perfect, optimize and secure it
        $this->handleProcesses($state); // Handler processes first

        if (!$this->flushMode) {
            $this->handleInput($state);
            $state->setSkipped(true);
        } elseif (!$this->finishedBuffers->isEmpty()) {
            $state->setOutput($this->finishedBuffers->dequeue());

            // After dequeue, stop flush
            if ($this->finishedBuffers->isEmpty()) {
                $this->flushMode = false;
            }
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * @param ProcessState $state
     */
    public function flush(ProcessState $state)
    {
        $this->flushMode = true;
        if (!$this->finishedBuffers->isEmpty()) {
            $state->setOutput($this->finishedBuffers->dequeue());
        } else {
            $state->setSkipped(true);
        }

        // After dequeue, stop flush
        if ($this->finishedBuffers->isEmpty() && !count($this->launchedProcesses)) {
            $this->flushMode = false;
        }
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @return bool
     */
    public function next(ProcessState $state)
    {
        $this->handleProcesses($state);

        // if there is some data waiting, handle it in priority
        if ($this->finishedBuffers->count() > 0) {
            $this->flushMode = true;

            return true;
        }

        // if we are in flush mode, we should wait for process to finish
        if ($this->flushMode) {
            return count($this->launchedProcesses) > 0;
        }

        sleep($this->getOption($state, 'sleep_on_finalize_interval'));

        return false;
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     */
    protected function handleInput(ProcessState $state)
    {
        $options = $this->getOptions($state);
        while (\count($this->launchedProcesses) >= $options['max_processes']) {
            $this->handleProcesses($state);
            sleep($options['sleep_interval']);
        }

        $process = $this->launchProcess($state);
        $this->launchedProcesses[] = $process;

        $logContext = [
            'input' => $process->getProcess()->getInput(),
        ];

        $this->logger->debug("Running command: {$process->getProcess()->getCommandLine()}", $logContext);

        sleep($options['sleep_interval_after_launch']);
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @return SubprocessInstance
     */
    protected function launchProcess(ProcessState $state)
    {
        $subprocess = new SubprocessInstance(
            $this->kernel,
            $this->getOption($state, 'process'),
            $state->getInput(),
            $this->getOption($state, 'context'),
            [
                SubprocessInstance::OPTION_JSON_BUFFERING => true,
            ]
        );

        return $subprocess->buildProcess()->start();
    }

    /**
     * @param ProcessState $state
     *
     * @throws RuntimeException
     */
    protected function handleProcesses(ProcessState $state)
    {
        foreach ($this->launchedProcesses as $key => $process) {
            if (!$process->getProcess()->isTerminated()) {
                // @todo handle incremental error output properly, specially for terminal where logs are lost
                echo $process->getProcess()->getIncrementalErrorOutput();
                continue;
            }

            $logContext = [
                'cmd' => $process->getProcess()->getCommandLine(),
                'input' => $process->getProcess()->getInput(),
                'exit_code' => $process->getProcess()->getExitCode(),
                'exit_code_text' => $process->getProcess()->getExitCodeText(),
            ];
            $this->logger->debug('Command terminated', $logContext);

            unset($this->launchedProcesses[$key]);
            if (0 !== $process->getProcess()->getExitCode()) {
                $this->logger->critical($process->getProcess()->getErrorOutput(), $logContext);
                $this->killProcesses();

                throw new \RuntimeException("Sub-process has failed: {$process->getProcess()->getExitCodeText()}");
            }

            $result = $process->getResult();
            if (isset($result)) {
                $this->finishedBuffers->enqueue($result);
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     * @throws InvalidConfigurationException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'process',
            ]
        );
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'process',
            function (Options $options, $value) {
                if (!$this->processRegistry->hasProcessConfiguration($value)) {
                    throw new InvalidConfigurationException("Unknown process {$value}");
                }

                return $value;
            }
        );
        $resolver->setDefaults(
            [
                'max_processes' => 3,
                'sleep_interval' => 1,
                'sleep_interval_after_launch' => 1,
                'sleep_on_finalize_interval' => 1,
                'process_options' => [],
                'context' => [],
            ]
        );
        $resolver->setAllowedTypes('max_processes', ['integer', 'double']);
        $resolver->setAllowedTypes('sleep_interval', ['integer', 'double']);
        $resolver->setAllowedTypes('sleep_interval_after_launch', ['integer', 'double']);
        $resolver->setAllowedTypes('context', ['array']);

        $resolver->setAllowedTypes('process_options', ['array']);
        $resolver->setNormalizer(
            'process_options',
            static function (Options $options, $value) {
                if (!empty($value)) {
                    // Todo deprecation trigger
                    throw new \InvalidArgumentException('Deprecated option, please contact support for help');
                }

                return $value;
            }
        );
    }

    /**
     * Kill all running processes
     */
    protected function killProcesses()
    {
        foreach ($this->launchedProcesses as $process) {
            $process->stop(5);
        }
    }
}
