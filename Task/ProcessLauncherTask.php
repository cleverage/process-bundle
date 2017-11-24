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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Launch a new process for each input received, input must be a scalar, a resource or a \Traversable
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessLauncherTask extends AbstractConfigurableTask implements FinalizableTaskInterface
{
    /** @var ProcessConfigurationRegistry */
    protected $processRegistry;

    /** @var KernelInterface */
    protected $kernel;

    /** @var Process[] */
    protected $launchedProcesses = [];

    /**
     * @param ProcessConfigurationRegistry $processRegistry
     * @param KernelInterface              $kernel
     */
    public function __construct(
        ProcessConfigurationRegistry $processRegistry,
        KernelInterface $kernel
    ) {
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
        while (count($this->launchedProcesses) >= $options['max_processes']) {
            $this->handleProcesses($state);
            sleep($options['sleep_interval']);
        }

        $this->launchedProcesses[] = $this->launchProcess($state);
        sleep($options['sleep_interval_after_launch']);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function finalize(ProcessState $state)
    {
        $processCount = count($this->launchedProcesses);
        if (0 === $processCount) {
            return;
        }

        $output = $state->getConsoleOutput();
        while (count($this->launchedProcesses) > 0) {
            $processCount = count($this->launchedProcesses);
            if ($output) {
                $output->writeln("<info>Waiting for {$processCount} processes to end...</info>");
            }
            $this->handleProcesses($state);
            sleep($this->getOption($state, 'sleep_on_finalize_interval'));
        }
        if ($output) {
            $output->writeln('<info>No more process !</info>');
        }
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
        $processBuilder = new ProcessBuilder();
        $pathFinder = new PhpExecutableFinder();
        $consolePath = $this->kernel->getRootDir().'/../bin/console';
        $logDir = $this->kernel->getLogDir().'/process';
        $processCode = $this->getOption($state, 'process');

        $fs = new Filesystem();
        $fs->mkdir($logDir);
        if (!$fs->exists($consolePath)) {
            throw new \RuntimeException("Unable to resolve path to symfony console '{$consolePath}'");
        }

        $consoleOutput = $state->getConsoleOutput();
        $arguments = [
            $pathFinder->find(),
            $consolePath,
            '--env='.$this->kernel->getEnvironment(),
            'cleverage:process:execute',
            '--input-from-stdin',
            $processCode,
        ];
        $verbosity = $this->getVerbosityParameter($consoleOutput);
        if ($verbosity) {
            $arguments[] = $verbosity;
        }

        // Even if parent process is launched with nohup, all subprocesses are sensitive to SIGHUP so we need to prepend
        // this all the time. Sub-processes are still sensitive to other signal so there is no risk here.
        $processBuilder->setPrefix('nohup');

        $processBuilder->setArguments($arguments);
        /** @noinspection PhpParamsInspection */
        $processBuilder->setInput($state->getInput());
        $processBuilder->enableOutput();
        $process = $processBuilder->getProcess();

        if ($consoleOutput) {
            $consoleOutput->writeln("<info>{$process->getCommandLine()}</info>");
            if ($consoleOutput->isVeryVerbose() && function_exists('dump')) {
                $consoleOutput->writeln("<info>Input:</info>");
                dump($state->getInput());
            }
        }

        $process->start(
            function ($type, $output) use ($consoleOutput) {
                if ($consoleOutput) {
                    if ($type === 'err') {
                        $consoleOutput->write('<error>'.$output.'</error>');
                    } else {
                        $consoleOutput->write($output);
                    }
                }
            }
        );

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
        $resolver->setDefaults([
            'max_processes' => 5,
            'sleep_interval' => 1,
            'sleep_interval_after_launch' => 3,
            'sleep_on_finalize_interval' => 10,
        ]);
        $resolver->setAllowedTypes('max_processes', ['integer']);
        $resolver->setAllowedTypes('sleep_interval', ['integer']);
        $resolver->setAllowedTypes('sleep_interval_after_launch', ['integer']);
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

    /**
     * @param OutputInterface|null $output
     *
     * @return string
     */
    protected function getVerbosityParameter(OutputInterface $output = null)
    {
        if (!$output) {
            return null;
        }
        switch($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_QUIET:
                return '-q';
            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';
            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';
        }

        return null;
    }
}
