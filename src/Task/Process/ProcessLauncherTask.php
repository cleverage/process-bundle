<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Launch a new process for each input received, input must be a scalar, a resource or a \Traversable.
 */
class ProcessLauncherTask extends AbstractConfigurableTask implements FlushableTaskInterface, IterableTaskInterface
{
    /**
     * @var SubprocessInstance[]
     */
    protected array $launchedProcesses = [];

    protected \SplQueue $finishedBuffers;

    protected bool $flushMode = false;

    public function __construct(
        protected LoggerInterface $logger,
        protected ProcessConfigurationRegistry $processRegistry,
        protected KernelInterface $kernel
    ) {
        $this->finishedBuffers = new \SplQueue();
    }

    public function execute(ProcessState $state): void
    {
        // TODO still not perfect, optimize and secure it
        $this->handleProcesses($state); // Handler processes first

        if (!$this->flushMode) {
            $this->handleInput($state);
            $state->setSkipped(true);
        } elseif (!$this->finishedBuffers->isEmpty()) {
            $state->setOutput($this->finishedBuffers->dequeue());

            // After dequeue, stop flush
            /* @phpstan-ignore-next-line */
            if ($this->finishedBuffers->isEmpty()) {
                $this->flushMode = false;
            }
        } else {
            $state->setSkipped(true);
        }
    }

    public function flush(ProcessState $state): void
    {
        $this->flushMode = true;
        if (!$this->finishedBuffers->isEmpty()) {
            $state->setOutput($this->finishedBuffers->dequeue());
        } else {
            $state->setSkipped(true);
        }

        // After dequeue, stop flush
        if ($this->finishedBuffers->isEmpty() && !\count($this->launchedProcesses)) {
            $this->flushMode = false;
        }
    }

    public function next(ProcessState $state): bool
    {
        $this->handleProcesses($state);

        // if there is some data waiting, handle it in priority
        if ($this->finishedBuffers->count() > 0) {
            $this->flushMode = true;

            return true;
        }

        // if we are in flush mode, we should wait for process to finish
        if ($this->flushMode) {
            return \count($this->launchedProcesses) > 0;
        }

        usleep($this->getOption($state, 'sleep_on_finalize_interval'));

        return false;
    }

    protected function handleInput(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        while (\count($this->launchedProcesses) >= $options['max_processes']) {
            $this->handleProcesses($state);
            usleep($options['sleep_interval']);
        }

        $process = $this->launchProcess($state);
        $this->launchedProcesses[] = $process;

        $logContext = [
            'input' => $process->getProcess()
                ->getInput(),
        ];

        $this->logger->debug("Running command: {$process->getProcess()->getCommandLine()}", $logContext);

        usleep($options['sleep_interval_after_launch']);
    }

    protected function launchProcess(ProcessState $state): SubprocessInstance
    {
        $input = null !== $state->getInput() ? (string) $state->getInput() : null;

        $subprocess = new SubprocessInstance(
            $this->kernel,
            $this->getOption($state, 'process'),
            $input,
            $this->getOption($state, 'context'),
            [
                SubprocessInstance::OPTION_JSON_BUFFERING => $this->getOption($state, 'json_buffering'),
            ]
        );

        return $subprocess->buildProcess()
            ->start();
    }

    protected function handleProcesses(ProcessState $state): void
    {
        foreach ($this->launchedProcesses as $key => $process) {
            if (!$process->getProcess()->isTerminated()) {
                // @todo handle incremental error output properly, specially for terminal where logs are lost
                echo $process->getProcess()
                    ->getIncrementalErrorOutput();
                continue;
            }

            $logContext = [
                'cmd' => $process->getProcess()
                    ->getCommandLine(),
                'input' => $process->getProcess()
                    ->getInput(),
                'exit_code' => $process->getProcess()
                    ->getExitCode(),
                'exit_code_text' => $process->getProcess()
                    ->getExitCodeText(),
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

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['process']);
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
                'json_buffering' => false,
            ]
        );
        $resolver->setAllowedTypes('max_processes', ['integer']);

        $resolver->setAllowedTypes('sleep_interval', ['integer', 'double']);
        $resolver->setAllowedTypes('sleep_interval_after_launch', ['integer', 'double']);
        $resolver->setAllowedTypes('sleep_on_finalize_interval', ['integer', 'double']);
        $microsecondNormalizer = fn (Options $options, $value): int => (int) ($value * 1_000_000);
        $resolver->setNormalizer('sleep_interval', $microsecondNormalizer);
        $resolver->setNormalizer('sleep_interval_after_launch', $microsecondNormalizer);
        $resolver->setNormalizer('sleep_on_finalize_interval', $microsecondNormalizer);

        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setAllowedTypes('json_buffering', ['boolean']);

        $resolver->setAllowedTypes('process_options', ['array']);
        $resolver->setNormalizer(
            'process_options',
            static function (Options $options, $value): int|float|string|bool|null {
                if (!empty($value)) {
                    // Todo deprecation trigger
                    throw new \InvalidArgumentException('Deprecated option, please contact support for help');
                }

                return $value;
            }
        );
    }

    /**
     * Kill all running processes.
     */
    protected function killProcesses(): void
    {
        foreach ($this->launchedProcesses as $process) {
            $process->stop(5);
        }
    }
}
