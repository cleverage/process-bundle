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

namespace CleverAge\ProcessBundle\Model;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class SubprocessInstance
{
    final public const OPTION_JSON_BUFFERING = 'json-buffering';

    protected Process $process;

    protected string $bufferPath;

    protected array $options;

    protected string $consolePath;

    protected string $environment;

    protected string $logDir;

    public function __construct(
        KernelInterface $kernel,
        protected string $processCode,
        protected ?string $input,
        protected array $context = [],
        array $options = []
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->consolePath = $kernel->getProjectDir() . '/bin/console';
        $this->environment = $kernel->getEnvironment();
        $this->bufferPath = $kernel->getProjectDir() . '/var/cdm_buffer_' . uniqid() . '.json-stream'; // Todo use param ?
        $this->logDir = $kernel->getLogDir() . '/process';
    }

    /**
     * Prepare the process before start
     *
     * @return $this
     */
    public function buildProcess()
    {
        $pathFinder = new PhpExecutableFinder();

        $arguments = [
            'nohup',
            $pathFinder->find(),
            $this->consolePath,
            '--env=' . $this->environment,
            'cleverage:process:execute',
            '--input-from-stdin',
        ];

        $fs = new Filesystem();
        $fs->mkdir($this->logDir);
        if (! $fs->exists($this->consolePath)) {
            throw new RuntimeException("Unable to resolve path to symfony console '{$this->consolePath}'");
        }

        if ($this->options[self::OPTION_JSON_BUFFERING]) {
            $arguments = array_merge($arguments, ['--output=' . $this->bufferPath, '--output-format=json-stream']);
        }

        if (! empty($this->context)) {
            foreach ($this->context as $key => $value) {
                $arguments[] = sprintf('--context=%s:%s', $key, $value);
            }
        }

        $arguments[] = $this->processCode;

        $this->process = Process::fromShellCommandline($this->process->getCommandLine(), null, null, $this->input);
        $this->process->enableOutput();

        return $this;
    }

    /**
     * Start the process
     *
     * @return $this
     */
    public function start(): static
    {
        $this->process->start();

        return $this;
    }

    /**
     * Stop the process
     *
     * @return $this
     */
    public function stop(float $timeout = 10): static
    {
        $this->process->stop($timeout);

        return $this;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function getProcessCode(): string
    {
        return $this->processCode;
    }

    public function getInput(): ?string
    {
        return $this->input;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getResult(): ?string
    {
        $fs = new Filesystem();
        if ($this->process->isTerminated() && $fs->exists($this->bufferPath)) {
            return $this->bufferPath;
        }

        return null;
    }

    /**
     * Available options for process launcher
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_JSON_BUFFERING, false);
        $resolver->setAllowedTypes(self::OPTION_JSON_BUFFERING, 'bool');
    }
}
