<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
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

class SubprocessInstance
{
    public const OPTION_JSON_BUFFERING = 'json-buffering';

    /** @var Process */
    protected $process;

    /** @var string */
    protected $bufferPath;

    /** @var string */
    protected $processCode;

    /** @var string|null */
    protected $input;

    /** @var array */
    protected $options;

    /** @var array */
    protected $context;

    /** @var string */
    protected $consolePath;

    /** @var string */
    protected $environment;

    /** @var string */
    protected $logDir;

    /**
     * SubprocessInstance constructor.
     *
     * @param KernelInterface $kernel
     * @param string          $processCode
     * @param string|null     $input
     * @param array           $context
     * @param array           $options
     */
    public function __construct(
        KernelInterface $kernel,
        string $processCode,
        ?string $input,
        array $context = [],
        array $options = []
    ) {
        $this->processCode = $processCode;
        $this->input = $input;
        $this->context = $context;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->consolePath = $kernel->getProjectDir().'/bin/console';
        $this->environment = $kernel->getEnvironment();
        $this->bufferPath = $kernel->getProjectDir().'/var/cdm_buffer_'.uniqid().'.json-stream'; // Todo use param ?
        $this->logDir = $kernel->getLogDir().'/process';
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
            '--env='.$this->environment,
            'cleverage:process:execute',
            '--input-from-stdin',
        ];

        $fs = new Filesystem();
        $fs->mkdir($this->logDir);
        if (!$fs->exists($this->consolePath)) {
            throw new \RuntimeException("Unable to resolve path to symfony console '{$this->consolePath}'");
        }

        if ($this->options[self::OPTION_JSON_BUFFERING]) {
            $arguments = array_merge(
                $arguments,
                [
                    '--output='.$this->bufferPath,
                    '--output-format=json-stream',
                ]
            );
        }

        if (!empty($this->context)) {
            foreach ($this->context as $key => $value) {
                $arguments[] = sprintf('--context=%s:%s', $key, $value);
            }
        }

        $arguments[] = $this->processCode;

        $this->process = new Process($arguments, null, null, $this->input);
        $this->process->setCommandLine($this->process->getCommandLine());
        $this->process->inheritEnvironmentVariables();
        $this->process->enableOutput();

        return $this;
    }

    /**
     * Start the process
     *
     * @return $this
     */
    public function start()
    {
        $this->process->start();

        return $this;
    }

    /**
     * Stop the process
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function stop($timeout = 10)
    {
        $this->process->stop($timeout);

        return $this;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }

    /**
     * @return string
     */
    public function getProcessCode(): string
    {
        return $this->processCode;
    }

    /**
     * @return string|null
     */
    public function getInput(): ?string
    {
        return $this->input;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return string|null
     */
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
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(self::OPTION_JSON_BUFFERING, false);
        $resolver->setAllowedTypes(self::OPTION_JSON_BUFFERING, 'bool');
    }
}
