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

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List all configured processes
 */
class ListProcessCommand extends Command
{
    protected static $defaultName = 'cleverage:process:list';

    protected static $defaultDescription = 'List defined process';

    public function __construct(
        protected ProcessConfigurationRegistry $processConfigRegistry
    ) {
        parent::__construct();
    }

    /**
     * Counter callback for public processes
     *
     * @param int                  $sum
     */
    public function publicProcessCounter($sum, ProcessConfiguration $processConfiguration): int
    {
        return $sum + ($processConfiguration->isPublic() ? 1 : 0);
    }

    /**
     * Counter callback for private processes
     *
     * @param int                  $sum
     */
    public function privateProcessCounter($sum, ProcessConfiguration $processConfiguration): int
    {
        return $sum + ($processConfiguration->isPrivate() ? 1 : 0);
    }

    /**
     * Sorter callback for process codes
     */
    public function processSorter(ProcessConfiguration $a, ProcessConfiguration $b): int
    {
        return $a->getCode() <=> $b->getCode();
    }

    /**
     * Filter callback to find max message length
     *
     * @param int   $max
     */
    public function maxMessageLengthFilter($max, array $message): int
    {
        return \max($max, strlen($this->filterOutTags($message['output'])));
    }

    protected function configure()
    {
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Shows all processes (including hidden ones)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $processConfigurations = $this->processConfigRegistry->getProcessConfigurations();
        \usort($processConfigurations, $this->processSorter(...));

        $publicCount = \array_reduce($processConfigurations, $this->publicProcessCounter(...), 0);
        $privateCount = \array_reduce($processConfigurations, $this->privateProcessCounter(...), 0);
        $output->writeln(
            "<info>There are {$publicCount} process configurations defined (and {$privateCount} private) :</info>"
        );

        $messages = [];
        foreach ($processConfigurations as $processConfiguration) {
            if ($processConfiguration->isPublic() || $input->getOption('all')) {
                $countTasks = \count($processConfiguration->getTaskConfigurations());
                $message = "<info> - </info>{$processConfiguration->getCode()}<info> with {$countTasks} tasks</info>";

                if ($processConfiguration->isPrivate()) {
                    $message .= ' <comment>(private)</comment>';
                }

                $messages[] = [
                    'process' => $processConfiguration,
                    'output' => $message,
                ];
            }
        }

        // Add process descriptions at a fixed position
        $maxMessageLength = \array_reduce($messages, $this->maxMessageLengthFilter(...), 0);
        $outputMessages = [];
        foreach ($messages as $message) {
            /** @var ProcessConfiguration $processConfiguration */
            $processConfiguration = $message['process'];
            $outputMessage = $message['output'];

            if ($processConfiguration->getDescription()) {
                $outputMessage = $this->padMessage($outputMessage, $maxMessageLength + 3);
                $outputMessage .= $processConfiguration->getDescription();
            }

            $outputMessages[] = $outputMessage;
        }

        // Output messages
        foreach ($outputMessages as $message) {
            $output->writeln($message);
        }

        return Command::SUCCESS;
    }

    /**
     * Returns a padded message (without counting metadata)
     *
     * @param int    $length
     */
    protected function padMessage(string $message, $length = 80): string
    {
        $currentLen = strlen($this->filterOutTags($message));
        if ($currentLen < $length) {
            $message .= str_repeat(' ', $length - $currentLen);
        }

        return $message;
    }

    /**
     * Filter out tags used in console outputs
     */
    protected function filterOutTags(string $string): string
    {
        return preg_replace('/<[^<>]*>/', '', $string);
    }
}
