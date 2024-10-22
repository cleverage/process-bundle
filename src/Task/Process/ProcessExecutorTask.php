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

use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Execute one or many processes while chaining inputs in a iterable way.
 */
class ProcessExecutorTask extends AbstractConfigurableTask
{
    protected ?string $process = null;

    public function __construct(
        protected ProcessManager $processManager,
        protected ProcessConfigurationRegistry $processRegistry,
        protected LoggerInterface $logger,
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $input = $state->getInput();
        $process = $this->getOption($state, 'process');

        $output = $this->processManager->execute($process, $input, $this->getOption($state, 'context'));
        $state->setOutput($output);
    }

    public function initialize(ProcessState $state): void
    {
        parent::initialize($state);
        $this->process = $this->getOption($state, 'process');
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('process');
        $resolver->setDefaults([
            'context' => [],
        ]);
        $resolver->addAllowedTypes('process', 'string');
        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setNormalizer(
            'process',
            function (Options $options, $processCode) {
                if (!$this->processRegistry->hasProcessConfiguration($processCode)) {
                    throw new InvalidConfigurationException("Unknown process {$processCode}");
                }

                return $processCode;
            }
        );
    }
}
