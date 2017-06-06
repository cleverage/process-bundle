<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
