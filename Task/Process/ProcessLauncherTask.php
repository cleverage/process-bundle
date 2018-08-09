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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Launch a new process for each input received, input must be a scalar, a resource or a \Traversable
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessLauncherTask extends AbstractConfigurableTask implements FlushableTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ProcessConfigurationRegistry */
    protected $processRegistry;

    /** @var KernelInterface */
    protected $kernel;

    /** @var Process[] */
    protected $launchedProcesses = [];

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
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $this->handleProcesses($state); // Handler processes first

        $options = $this->getOptions($state);
        while (\count($this->launchedProcesses) >= $options['max_processes']) {
            $this->handleProcesses($state);
            sleep($options['sleep_interval']);
        }

        $process = $this->launchProcess($state);
        $this->launchedProcesses[] = $process;

        $logContext = $state->getLogContext();
        $logContext['cmd'] = $process->getCommandLine();
        $logContext['input'] = $process->getInput();
        $this->logger->debug('Running command', $logContext);

        sleep($options['sleep_interval_after_launch']);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function flush(ProcessState $state)
    {
        while (\count($this->launchedProcesses) > 0) {
            $this->handleProcesses($state);
            sleep($this->getOption($state, 'sleep_on_finalize_interval'));
        }

        $state->setSkipped(true);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function launchProcess(ProcessState $state)
    {
        $pathFinder = new PhpExecutableFinder();
        $consolePath = $this->kernel->getRootDir().'/../bin/console';
        $logDir = $this->kernel->getLogDir().'/process';
        $processCode = $this->getOption($state, 'process');
        $processOptions = $this->getOption($state, 'process_options');

        $fs = new Filesystem();
        $fs->mkdir($logDir);
        if (!$fs->exists($consolePath)) {
            throw new \RuntimeException("Unable to resolve path to symfony console '{$consolePath}'");
        }

        $arguments = [
            'nohup',
            $pathFinder->find(),
            $consolePath,
            '--env='.$this->kernel->getEnvironment(),
            'cleverage:process:execute',
            '--input-from-stdin',
        ];

        $arguments = array_merge($arguments, $processOptions);
        $arguments[] = $processCode;

        $process = new Process($arguments, null, null, $state->getInput());
        $process->setCommandLine($process->getCommandLine());
        $process->inheritEnvironmentVariables();
        $process->enableOutput();
        $process->start();

        return $process;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    protected function handleProcesses(ProcessState $state)
    {
        foreach ($this->launchedProcesses as $key => $process) {
            if (!$process->isTerminated()) {
                continue;
            }

            $logContext = $state->getLogContext();
            $logContext['cmd'] = $process->getCommandLine();
            $logContext['input'] = $process->getInput();
            $logContext['exit_code'] = $process->getExitCode();
            $logContext['exit_code_text'] = $process->getExitCodeText();
            $this->logger->debug('Command terminated', $logContext);

            unset($this->launchedProcesses[$key]);
            if (0 !== $process->getExitCode()) {
                $state->addErrorContextValue('subprocess_cmd', $process->getCommandLine());
                $state->addErrorContextValue('subprocess_exit_code', $process->getExitCode());
                $state->stop(new \RuntimeException("Sub-process has failed: {$process->getExitCodeText()}"));

                $this->killProcesses();

                return;
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
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
            ]
        );
        $resolver->setAllowedTypes('max_processes', ['integer']);
        $resolver->setAllowedTypes('sleep_interval', ['integer']);
        $resolver->setAllowedTypes('sleep_interval_after_launch', ['integer']);
        $resolver->setAllowedTypes('process_options', ['array']);
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
