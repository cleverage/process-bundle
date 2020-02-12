<?php


namespace CleverAge\ProcessBundle\Event;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event object used during CLI process manipulation
 */
class ConsoleProcessEvent extends Event
{
    const EVENT_CLI_INIT = 'cleverage_process.cli.init';

    /** @var InputInterface */
    private $consoleInput;

    /** @var OutputInterface */
    private $consoleOutput;

    /** @var mixed */
    private $processInput;

    /** @var array */
    private $processContext;

    /**
     * ConsoleProcessEvent constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param mixed           $processInput
     * @param array           $processContext
     */
    public function __construct(InputInterface $input, OutputInterface $output, $processInput, array $processContext)
    {
        $this->consoleInput = $input;
        $this->consoleOutput = $output;
        $this->processInput = $processInput;
        $this->processContext = $processContext;
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
