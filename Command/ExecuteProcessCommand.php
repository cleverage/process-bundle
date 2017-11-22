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

use CleverAge\ProcessBundle\Manager\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run a process from the command line interface
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ExecuteProcessCommand extends ContainerAwareCommand
{
    /** @var ProcessManager */
    protected $processManager;

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('cleverage:process:execute');
        $this->addArgument(
            'processCodes',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'The code(s) of the process(es) to execute, separated by a space'
        );
        $this->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Pass input data to the first task of the process');
        $this->addOption('input-from-stdin', null, InputOption::VALUE_NONE, 'Read input data from stdin');
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
        $this->processManager = $this->getContainer()->get('cleverage_process.manager.process');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputData = $input->getOption('input');
        if ($input->getOption('input-from-stdin')) {
            $inputData = '';
            while (!feof(STDIN)) {
                $inputData .= fread(STDIN, 8192);
            }
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($input->getArgument('processCodes') as $code) {
            if (!$output->isQuiet()) {
                $output->writeln("<comment>Starting process '{$code}'...</comment>");
            }
            $returnValue = $this->processManager->execute($code, $output, $inputData);
            if ($output->isVeryVerbose() && function_exists('dump')) {
                dump($returnValue);
            }
            if (!$output->isQuiet()) {
                $output->writeln("<info>Process '{$code}' executed successfully</info>");
            }
        }

        return 0;
    }
}
