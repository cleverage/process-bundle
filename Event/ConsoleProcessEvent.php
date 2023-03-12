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
    final public const EVENT_CLI_INIT = 'cleverage_process.cli.init';

    /**
     * ConsoleProcessEvent constructor.
     */
    public function __construct(private readonly InputInterface $consoleInput, private readonly OutputInterface $consoleOutput, private readonly mixed $processInput, private readonly array $processContext)
    {
    }


    public function getConsoleInput(): InputInterface
    {
        return $this->consoleInput;
    }

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

    public function getProcessContext(): array
    {
        return $this->processContext;
    }
}
