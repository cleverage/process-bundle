<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Command;

use CleverAge\ProcessBundle\Event\ConsoleProcessEvent;
use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\Yaml\Parser;

/**
 * Run a process from the command line interface
 */
#[AsCommand(name: 'cleverage:process:execute', description: 'Execute a process',)]
class ExecuteProcessCommand extends Command
{
    final public const OUTPUT_STDOUT = '-';

    final public const OUTPUT_FORMAT_DUMP = 'dump';

    final public const OUTPUT_FORMAT_JSON = 'json-stream';

    public function __construct(
        protected ProcessManager $processManager,
        protected EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument(
            'processCodes',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'The code(s) of the process(es) to execute, separated by a space'
        );
        $this->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Pass input data to the first task of the process');
        $this->addOption('input-from-stdin', null, InputOption::VALUE_NONE, 'Read input data from stdin');
        $this->addOption(
            'context',
            'c',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Contextual value',
            []
        );
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output path to dump data ("-" to use STDOUT with symfony dumper)',
            self::OUTPUT_STDOUT
        );
        $this->addOption('output-format', 't', InputOption::VALUE_OPTIONAL, 'Output format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputData = $input->getOption('input');
        if ($input->getOption('input-from-stdin')) {
            $inputData = '';
            while (! feof(STDIN)) {
                $inputData .= fread(STDIN, 8192);
            }
        }

        $context = $this->parseContextValues($input);

        $this->eventDispatcher->dispatch(new ConsoleProcessEvent($input, $output, $inputData, $context));

        foreach ($input->getArgument('processCodes') as $code) {
            if (! $output->isQuiet()) {
                $output->writeln("<comment>Starting process '$code'...</comment>");
            }

            // Execute each process
            $returnValue = $this->processManager->execute($code, $inputData, $context);
            $this->handleOutputData($returnValue, $input, $output);

            if (! $output->isQuiet()) {
                $output->writeln("<info>Process '$code' executed successfully</info>");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseContextValues(InputInterface $input): array
    {
        $parser = new Parser();

        $pattern = '/(\w+):(.*)/';
        $contextValues = $input->getOption('context');
        $context = [];
        foreach ($contextValues as $contextValue) {
            preg_match($pattern, (string) $contextValue, $parts);
            if (\count($parts) !== 3
                || $parts[0] !== $contextValue) {
                throw new InvalidArgumentException(sprintf('Invalid context %s', $contextValue));
            }
            $context[$parts[1]] = $parser->parse($parts[2]);
        }

        return $context;
    }

    protected function handleOutputData(mixed $data, InputInterface $input, OutputInterface $output): void
    {
        // Skip all if undefined
        if (! $input->getOption('output-format')) {
            return;
        }

        // Handle printing the output
        if ($input->getOption('output') === self::OUTPUT_STDOUT) {
            if ($output->isVeryVerbose()) {
                if ($input->getOption('output-format') === self::OUTPUT_FORMAT_DUMP && class_exists(VarDumper::class)) {
                    VarDumper::dump($data); // @todo remove this please
                } elseif ($input->getOption('output-format') === self::OUTPUT_FORMAT_JSON) {
                    $output->writeln(json_encode($data, JSON_THROW_ON_ERROR));
                } else {
                    throw new InvalidArgumentException(
                        sprintf("Cannot handle data output with format '%s'", $input->getOption('output-format'))
                    );
                }
            }
        } elseif ($input->getOption('output-format') === self::OUTPUT_FORMAT_JSON) {
            // JsonStreamFile::writeLine only takes an array...
            // TODO how to handle other cases ?
            if (\is_array($data)) {
                $outputFile = new JsonStreamFile($input->getOption('output'), 'wb');
                $outputFile->writeLine($data);
            }

            if ($output->isVerbose() && isset($outputFile)) {
                $output->writeln(sprintf("Output stored in '%s'", $input->getOption('output')));
            }
        } else {
            throw new InvalidArgumentException(
                sprintf("Cannot handle data output with format '%s'", $input->getOption('output-format'))
            );
        }
    }
}
