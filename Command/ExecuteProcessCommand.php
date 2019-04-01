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

use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\Yaml\Parser;

/**
 * Run a process from the command line interface
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ExecuteProcessCommand extends Command
{

    const OUTPUT_STDOUT = '-';

    const OUTPUT_FORMAT_DUMP = 'dump';
    const OUTPUT_FORMAT_JSON = 'json-stream';

    /** @var ProcessManager */
    protected $processManager;

    /**
     * @param ProcessManager $processManager
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
        parent::__construct();
    }

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
        $this->addOption('context', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Contextual value', []);
        $this->addOption('output', 'o',
            InputOption::VALUE_REQUIRED,
            'Output path to dump data ("-" to use STDOUT with symfony dumper)',
            self::OUTPUT_STDOUT);
        $this->addOption('output-format', 't',
            InputOption::VALUE_OPTIONAL,
            'Output format',
            null);
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

        $context = $this->parseContextValues($input);

        /** @noinspection ForeachSourceInspection */
        foreach ($input->getArgument('processCodes') as $code) {
            if (!$output->isQuiet()) {
                $output->writeln("<comment>Starting process '{$code}'...</comment>");
            }

            // Execute each process
            $returnValue = $this->processManager->execute($code, $inputData, $context);
            $this->handleOutputData($returnValue, $input, $output);

            if (!$output->isQuiet()) {
                $output->writeln("<info>Process '{$code}' executed successfully</info>");
            }
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     *
     * @return array
     */
    protected function parseContextValues(InputInterface $input)
    {
        $parser = new Parser();

        $pattern = '/([\w]+):(.*)/';
        $contextValues = $input->getOption('context');
        $context = [];
        foreach ($contextValues as $contextValue) {
            preg_match($pattern, $contextValue, $parts);
            if (3 !== \count($parts)
                || $parts[0] !== $contextValue) {
                throw new \InvalidArgumentException(sprintf('Invalid context %s', $contextValue));
            }
            $context[$parts[1]] = $parser->parse($parts[2]);
        }

        return $context;
    }

    protected function handleOutputData($data, InputInterface $input, OutputInterface $output)
    {
        // Skip all if undefined
        if (!$input->getOption('output-format')) {
            return;
        }

        // Handle printing the output
        if ($input->getOption('output') === self::OUTPUT_STDOUT) {
            if ($output->isVeryVerbose()) {
                if ($input->getOption('output-format') === self::OUTPUT_FORMAT_DUMP && class_exists(VarDumper::class)) {
                    VarDumper::dump($data); // @todo remove this please
                } elseif ($input->getOption('output-format') === self::OUTPUT_FORMAT_JSON) {
                    $output->writeln(json_encode($data));
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        "Cannot handle data output with format '%s'",
                        $input->getOption('output-format')
                    ));
                }
            }
        } elseif ($input->getOption('output-format') === self::OUTPUT_FORMAT_JSON) {
            $outputFile = new JsonStreamFile($input->getOption('output'), 'wb');
            $outputFile->writeLine($data);

            if ($output->isVerbose()) {
                $output->writeln(sprintf("Output stored in '%s'", $input->getOption('output')));
            }
        } else {
            throw new \InvalidArgumentException(sprintf(
                "Cannot handle data output with format '%s'",
                $input->getOption('output-format')
            ));
        }
    }
}
