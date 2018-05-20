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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List all configured processes
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ListProcessCommand extends Command
{
    /** @var ProcessConfigurationRegistry */
    protected $processConfigRegistry;

    /**
     * @param ProcessConfigurationRegistry $processConfigRegistry
     */
    public function __construct(ProcessConfigurationRegistry $processConfigRegistry)
    {
        $this->processConfigRegistry = $processConfigRegistry;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('cleverage:process:list');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processConfigurations = $this->processConfigRegistry->getProcessConfigurations();
        $processConfigurationCount = \count($processConfigurations);
        $output->writeln("<info>There are {$processConfigurationCount} process configurations defined :</info>");
        foreach ($processConfigurations as $processConfiguration) {
            $countTasks = \count($processConfiguration->getTaskConfigurations());
            $output->writeln(
                "<info> - </info>{$processConfiguration->getCode()}<info> with {$countTasks} tasks</info>"
            );
        }
    }
}
