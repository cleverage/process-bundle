<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Command;

use CleverAge\ProcessBundle\Configuration\DidYouMean;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Event\ConsoleProcessEvent;
use CleverAge\ProcessBundle\Exception\InvalidArgumentException;
use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\Yaml\Parser;

/**
 * Run a process from the command line interface.
 *
 * @internal
 */
#[AsCommand(name: 'cleverage:process:execute', description: 'Execute a process', )]
class ExecuteProcessCommand extends Command
{
    final public const OUTPUT_STDOUT = '-';

    final public const OUTPUT_FORMAT_DUMP = 'dump';

    final public const OUTPUT_FORMAT_JSON = 'json-stream';

    public function __construct(
        protected ProcessManager $processManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected ProcessConfigurationRegistry $processRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'processCodes',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'The code(s) of the process(es) to execute, separated by a space',
            null,
            // Shell completion: the codes of the processes whose configuration is valid
            fn (): array => array_keys($this->processRegistry->getProcessConfigurations())
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
        $this->addOption('output-format', 't', InputOption::VALUE_OPTIONAL, 'Output format', null, [self::OUTPUT_FORMAT_DUMP, self::OUTPUT_FORMAT_JSON]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputData = $input->getOption('input');
        if ($input->getOption('input-from-stdin')) {
            $inputData = '';
            while (!feof(\STDIN)) {
                $inputData .= fread(\STDIN, 8192);
            }
        }

        $context = $this->parseContextValues($input);

        // Checked before executing anything: a wrong option must not be detected once the work is done
        foreach ($input->getArgument('processCodes') as $code) {
            if (!$this->processRegistry->hasProcessConfiguration($code)) {
                throw new InvalidConfigurationException(\sprintf('Unknown process %s.%s The processes are listed by the cleverage:process:list command.', $code, DidYouMean::suggest($code, $this->processRegistry->getProcessCodes())));
            }
        }
        $this->checkOutputFormat($input);
        if (null !== $inputData) {
            $this->warnAboutIgnoredInput($input->getArgument('processCodes'), $output);
        }

        $this->eventDispatcher->dispatch(new ConsoleProcessEvent($input, $output, $inputData, $context));

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
            if (3 !== \count($parts)
                || $parts[0] !== $contextValue) {
                throw new InvalidArgumentException(\sprintf('Invalid context %s: expected key:value, e.g. -c date:2024-01-01 (the value is parsed as YAML)', $contextValue));
            }
            $context[$parts[1]] = $parser->parse($parts[2]);
        }

        return $context;
    }

    /**
     * The input of a process without entry point is ignored: only logged as a warning by the process manager, which
     * is not displayed without -v (or without the MonologBundle).
     *
     * @param array<string> $processCodes
     */
    protected function warnAboutIgnoredInput(array $processCodes, OutputInterface $output): void
    {
        foreach ($processCodes as $code) {
            try {
                $hasEntryPoint = $this->processRegistry->getProcessConfiguration($code)->getEntryPoint() instanceof TaskConfiguration;
            } catch (\Throwable) {
                // An invalid configuration fails when the process is executed
                continue;
            }
            if (!$hasEntryPoint) {
                $this->getErrorOutput($output)->writeln("<comment>The input is ignored by the process '{$code}': it has no entry_point</comment>");
            }
        }
    }

    /**
     * The output format must be "dump" (to STDOUT, with the VarDumper component) or "json-stream".
     */
    protected function checkOutputFormat(InputInterface $input): void
    {
        $format = $input->getOption('output-format');
        if (!$format) {
            return;
        }
        $toStdout = self::OUTPUT_STDOUT === $input->getOption('output');
        if (self::OUTPUT_FORMAT_JSON === $format || ($toStdout && self::OUTPUT_FORMAT_DUMP === $format && class_exists(VarDumper::class))) {
            return;
        }
        if ($toStdout && self::OUTPUT_FORMAT_DUMP === $format) {
            throw new InvalidArgumentException("The 'dump' output format requires the VarDumper component: run \"composer require symfony/var-dumper\"");
        }

        $formats = [self::OUTPUT_FORMAT_DUMP, self::OUTPUT_FORMAT_JSON];

        throw new InvalidArgumentException(\sprintf("Cannot handle data output with format '%s': use '%s' (STDOUT only) or '%s'.%s", $format, self::OUTPUT_FORMAT_DUMP, self::OUTPUT_FORMAT_JSON, DidYouMean::suggest((string) $format, $formats)));
    }

    protected function handleOutputData(mixed $data, InputInterface $input, OutputInterface $output): void
    {
        // Skip all if undefined
        if (!$input->getOption('output-format')) {
            return;
        }

        // Handle printing the output: asked explicitly, whatever the verbosity
        if (self::OUTPUT_STDOUT === $input->getOption('output')) {
            if (self::OUTPUT_FORMAT_DUMP === $input->getOption('output-format')) {
                VarDumper::dump($data);
            } else {
                // Invalid UTF-8 replaced: the output of a process reading a non UTF-8 file can still be printed
                $json = json_encode($data, \JSON_INVALID_UTF8_SUBSTITUTE);
                if (false === $json) {
                    // Reported, as before 5.5 without -vv: the next processes are executed
                    $this->getErrorOutput($output)->writeln(\sprintf('<comment>Output not printed: it cannot be encoded as JSON (%s)</comment>', json_last_error_msg()));
                } else {
                    $output->writeln($json);
                }
            }

            return;
        }

        // JsonStreamFile::writeLine() only takes an array
        if (!\is_array($data)) {
            $this->getErrorOutput($output)->writeln(\sprintf("<comment>Output not stored in '%s': the JSON stream format requires an array, %s given</comment>", $input->getOption('output'), get_debug_type($data)));

            return;
        }
        // As on STDOUT, the invalid UTF-8 sequences are replaced (e.g. a value read from a Latin-1 file); the buffer of
        // the process_launcher task is written here too
        $outputFile = new JsonStreamFile($input->getOption('output'), 'wb', null, [\JSON_THROW_ON_ERROR, \JSON_INVALID_UTF8_SUBSTITUTE]);
        try {
            $outputFile->writeLine($data);
        } catch (\JsonException $exception) {
            $this->getErrorOutput($output)->writeln(\sprintf("<comment>Output not stored in '%s': it cannot be encoded as JSON (%s)</comment>", $input->getOption('output'), $exception->getMessage()));

            return;
        }
        if ($output->isVerbose()) {
            $output->writeln(\sprintf("Output stored in '%s'", $input->getOption('output')));
        }
    }

    private function getErrorOutput(OutputInterface $output): OutputInterface
    {
        return $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }
}
