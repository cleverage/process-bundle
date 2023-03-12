<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Event;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Event object used during CLI process manipulation
 */
class ConsoleProcessEvent extends GenericEvent
{
    const EVENT_CLI_INIT = 'cleverage_process.cli.init';

    /**
     * ConsoleProcessEvent constructor.
     *
     * @param InputInterface $consoleInput
     * @param OutputInterface $consoleOutput
     * @param mixed           $processInput
     * @param array           $processContext
     */
    public function __construct(private InputInterface $consoleInput, private OutputInterface $consoleOutput, private $processInput, private array $processContext)
    {
    }


    /**
     * @return InputInterface
     */
    public function getConsoleInput(): InputInterface
    {
        return $this->consoleInput;
    }

    /**
     * @return OutputInterface
     */
    public function getConsoleOutput(): OutputInterface
    {
        return $this->consoleOutput;
    }

    /**
     * @return mixed
     */
    public function getProcessInput()
    {
        return $this->processInput;
    }

    /**
     * @return array
     */
    public function getProcessContext(): array
    {
        return $this->processContext;
    }
}
