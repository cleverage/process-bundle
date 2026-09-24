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
 */
class CommandRunnerTask extends AbstractConfigurableTask
{
    public function __construct(
        protected KernelInterface $kernel,
    ) {
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
                'cwd' => $this->kernel->getProjectDir(), // This method is not actually in the interface, this is bad
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
