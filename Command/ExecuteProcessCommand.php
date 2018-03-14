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
            if ($returnValue !== 0) {
                if (!$output->isQuiet()) {
                    $output->writeln("<error>Process '{$code}' returned an error code</error>");
                }
                return $returnValue;
            }
            if (!$output->isQuiet()) {
                $output->writeln("<info>Process '{$code}' executed successfully</info>");
            }
        }

        return 0;
    }
}
