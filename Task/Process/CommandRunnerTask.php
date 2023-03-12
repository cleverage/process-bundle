<?php declare(strict_types=1);
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
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

/**
 * Launch a system command for each input, passing input to command
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CommandRunnerTask extends AbstractConfigurableTask
{
    /** @var KernelInterface */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @inheritDoc
     */
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $process = new Process(
            $options['commandline'],
            $options['cwd'],
            $options['env'],
            $state->getInput(),
            $options['timeout'],
        );
        $process->setOptions($options);
        $process->mustRun();
        $state->setOutput($process->getOutput());
    }

    /**
     * @inheritDoc
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'commandline',
            ]
        );
        $resolver->setAllowedTypes('commandline', ['string', 'array']);
        $resolver->setDefaults(
            [
                'cwd' => $this->kernel->getProjectDir(), // This method is not actually in the interface, this is bad
                'env' => null,
                'timeout' => 60,
                'options' => null,
            ]
        );
    }
}
