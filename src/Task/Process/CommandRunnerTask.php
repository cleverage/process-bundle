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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

/**
 * Launch a system command for each input, passing input to command.
 *
 * @final since 5.4
 */
class CommandRunnerTask extends AbstractConfigurableTask
{
    /**
     * @deprecated since 5.4, only set when the kernel is passed to the constructor
     */
    protected ?KernelInterface $kernel = null;

    protected string $projectDir;

    /**
     * @param string|KernelInterface $projectDir default working directory of the commands; passing the kernel is
     *                                           deprecated since 5.4
     */
    public function __construct(KernelInterface|string $projectDir)
    {
        if ($projectDir instanceof KernelInterface) {
            trigger_deprecation(
                'cleverage/process-bundle',
                '5.4',
                'Passing a "%s" to "%s::__construct()" is deprecated, pass the project directory instead. The argument will only accept a string in v6.0.',
                KernelInterface::class,
                self::class,
            );
            // @phpstan-ignore property.deprecated (kept for BC until v6.0)
            $this->kernel = $projectDir;
            $projectDir = $projectDir->getProjectDir();
        }
        $this->projectDir = $projectDir;
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $arguments = [
            $options['cwd'],
            $options['env'],
            $state->getInput(),
            $options['timeout'],
        ];
        $process = \is_array($options['commandline'])
            ? new Process($options['commandline'], ...$arguments)
            : Process::fromShellCommandline($options['commandline'], ...$arguments);
        if (null !== $options['options']) {
            $process->setOptions($options['options']);
        }
        $process->mustRun();
        $state->setOutput($process->getOutput());
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['commandline']);
        $resolver->setAllowedTypes('commandline', ['string', 'array']);
        $resolver->setDefaults(
            [
                'cwd' => $this->projectDir,
                'env' => null,
                'timeout' => 60,
                'options' => null,
            ]
        );
        $resolver->setAllowedTypes('cwd', ['null', 'string']);
        $resolver->setAllowedTypes('env', ['null', 'array']);
        $resolver->setAllowedTypes('timeout', ['null', 'int', 'float']);
        $resolver->setAllowedTypes('options', ['null', 'array']);
    }
}
