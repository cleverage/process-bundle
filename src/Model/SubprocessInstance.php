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

namespace CleverAge\ProcessBundle\Model;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @final since 5.4
 */
class SubprocessInstance
{
    final public const OPTION_JSON_BUFFERING = 'json-buffering';

    protected Process $process;

    protected string $bufferPath;

    /**
     * @var array<string, mixed>
     */
    protected array $options;

    protected string $consolePath;

    /**
     * @deprecated since 5.4, not used; only set when the kernel is passed to the constructor
     */
    protected ?string $logDir = null;

    /**
     * @param string|KernelInterface $consolePath path of the Symfony console; passing the kernel is deprecated since 5.4
     * @param array<string, mixed>   $context
     * @param array<string, mixed>   $options
     * @param string|null            $environment environment of the sub-process (--env), the default one of the console
     *                                            if null; taken from the kernel when it is passed
     */
    public function __construct(
        KernelInterface|string $consolePath,
        protected string $processCode,
        protected ?string $input,
        protected array $context = [],
        array $options = [],
        protected ?string $environment = null,
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        if ($consolePath instanceof KernelInterface) {
            trigger_deprecation(
                'cleverage/process-bundle',
                '5.4',
                'Passing a "%s" to "%s::__construct()" is deprecated, pass the path of the console and the environment instead. The argument will only accept a string in v6.0.',
                KernelInterface::class,
                self::class,
            );
            $this->environment ??= $consolePath->getEnvironment();
            $this->logDir = $consolePath->getLogDir().'/process';
            $consolePath = $consolePath->getProjectDir().'/bin/console';
        }
        $this->consolePath = $consolePath;
        // The project directory may be read-only (e.g. in a container)
        $this->bufferPath = sys_get_temp_dir().'/cdm_buffer_'.uniqid('', true).'.json-stream';
    }

    /**
     * Prepare the process before start.
     */
    public function buildProcess(): static
    {
        $phpBinary = (new PhpExecutableFinder())->find();
        if (false === $phpBinary) {
            throw new \RuntimeException('Unable to find the PHP executable');
        }

        $arguments = [
            'nohup',
            $phpBinary,
            $this->consolePath,
            ...(null !== $this->environment ? ['--env='.$this->environment] : []),
            'cleverage:process:execute',
            '--input-from-stdin',
        ];

        $fs = new Filesystem();
        if (!$fs->exists($this->consolePath)) {
            throw new \RuntimeException("Unable to resolve path to symfony console '{$this->consolePath}'");
        }

        if ($this->options[self::OPTION_JSON_BUFFERING]) {
            $arguments = [...$arguments, '--output='.$this->bufferPath, '--output-format=json-stream'];
        }

        foreach ($this->context as $key => $value) {
            $arguments[] = \sprintf('--context=%s:%s', $key, $value);
        }

        $arguments[] = $this->processCode;

        $this->process = new Process($arguments, null, null, $this->input);
        $this->process->enableOutput();

        return $this;
    }

    /**
     * Start the process.
     */
    public function start(): static
    {
        $this->process->start();

        return $this;
    }

    /**
     * Stop the process.
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

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, mixed>
     */
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
     * Available options for process launcher.
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_JSON_BUFFERING, false);
        $resolver->setAllowedTypes(self::OPTION_JSON_BUFFERING, 'bool');
    }
}
