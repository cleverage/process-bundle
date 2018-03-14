<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Command;

use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List all configured processes
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ListProcessCommand extends ContainerAwareCommand
{
    /** @var ProcessConfigurationRegistry */
    protected $processConfigRegistry;

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('cleverage:process:list');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->processConfigRegistry = $this->getContainer()->get('cleverage_process.registry.process_configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processConfigurations = $this->processConfigRegistry->getProcessConfigurations();
        $processConfigurationCount = count($processConfigurations);
        $output->writeln("<info>There are {$processConfigurationCount} process configurations defined :</info>");
        foreach ($processConfigurations as $processConfiguration) {
            $countTasks = count($processConfiguration->getTaskConfigurations());
            $output->writeln(
                "<info> - </info>{$processConfiguration->getCode()}<info> with {$countTasks} tasks</info>"
            );
        }
    }
}
