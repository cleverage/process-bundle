<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Process;

use CleverAge\ProcessBundle\Exception\InvalidArgumentException;
use CleverAge\ProcessBundle\Exception\RuntimeException;
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
 *
 * @final since 5.4
 */
class ProcessLauncherTask extends AbstractConfigurableTask implements FlushableTaskInterface, IterableTaskInterface
{
    /**
     * @var SubprocessInstance[]
     */
    protected array $launchedProcesses = [];

    /**
     * @var \SplQueue<string>
     */
    protected \SplQueue $finishedBuffers;

    protected bool $flushMode = false;

    /**
     * @deprecated since 5.4, only set when the kernel is passed to the constructor
     */
    protected ?KernelInterface $kernel = null;

    protected string $consolePath;

    /**
     * @param string|KernelInterface $consolePath path of the Symfony console running the sub-processes; passing the
     *                                            kernel is deprecated since 5.4
     * @param string|null            $environment environment of the sub-processes (--env), the default one of the
     *                                            console if null; taken from the kernel when it is passed
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected ProcessConfigurationRegistry $processRegistry,
        KernelInterface|string $consolePath,
        protected ?string $environment = null,
    ) {
        $this->finishedBuffers = new \SplQueue();
        if ($consolePath instanceof KernelInterface) {
            trigger_deprecation(
                'cleverage/process-bundle',
                '5.4',
                'Passing a "%s" to "%s::__construct()" is deprecated, pass the path of the console and the environment instead. The argument will only accept a string in v6.0.',
                KernelInterface::class,
                self::class,
            );
            // @phpstan-ignore property.deprecated (kept for BC until v6.0)
            $this->kernel = $consolePath;
            $this->environment ??= $consolePath->getEnvironment();
            $consolePath = $consolePath->getProjectDir().'/bin/console';
        }
        $this->consolePath = $consolePath;
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
        if ($this->finishedBuffers->isEmpty() && [] === $this->launchedProcesses) {
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
            return [] !== $this->launchedProcesses;
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
            $this->consolePath,
            $this->getOption($state, 'process'),
            $input,
            $this->getOption($state, 'context'),
            [
                SubprocessInstance::OPTION_JSON_BUFFERING => $this->getOption($state, 'json_buffering'),
            ],
            $this->environment,
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
                $process->removeBuffer();
                $this->logger->critical($process->getProcess()->getErrorOutput(), $logContext);
                $this->killProcesses();

                throw new RuntimeException("Sub-process has failed: {$process->getProcess()->getExitCodeText()}");
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
        $microsecondNormalizer = static fn (Options $options, $value): int => (int) ($value * 1_000_000);
        $resolver->setNormalizer('sleep_interval', $microsecondNormalizer);
        $resolver->setNormalizer('sleep_interval_after_launch', $microsecondNormalizer);
        $resolver->setNormalizer('sleep_on_finalize_interval', $microsecondNormalizer);

        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setAllowedTypes('json_buffering', ['boolean']);

        $resolver->setAllowedTypes('process_options', ['array']);
        $resolver->setNormalizer(
            'process_options',
            static function (Options $options, array $value): array {
                if ([] !== $value) {
                    // Deprecated option, removed in v6.0: a non-empty value is not supported anymore
                    throw new InvalidArgumentException('Deprecated option, please contact support for help');
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
            // An already terminated sub-process keeps its buffer: it is handled normally at the next call
            $running = !$process->getProcess()->isTerminated();
            $process->stop(5);
            if ($running) {
                $process->removeBuffer();
            }
        }
    }
}
