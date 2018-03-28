<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dump the content of the input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DebugTask implements TaskInterface
{
    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $input = $state->getInput();
        $console = $state->getConsoleOutput();
        if($console) {
            $processCode= $state->getProcessConfiguration()->getCode();
            $taskCode= $state->getTaskConfiguration()->getCode();
            $console->writeln("<info>DEBUG from {$processCode}::{$taskCode}</info>");
        }
        $this->printData($input, $console);
    }

    protected function printData($data, OutputInterface $output = null)
    {
        if (function_exists('dump')) {
            dump($data);
        } elseif ($output) {
            $output->writeln(print_r($data, true));
        }
    }
}
