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

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List all configured processes
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ListProcessCommand extends Command
{
    /** @var ProcessConfigurationRegistry */
    protected $processConfigRegistry;

    /**
     * @param ProcessConfigurationRegistry $processConfigRegistry
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ProcessConfigurationRegistry $processConfigRegistry)
    {
        $this->processConfigRegistry = $processConfigRegistry;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('cleverage:process:list');
        $this->setDescription('List defined process');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Shows all processes (including hidden ones)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processConfigurations = $this->processConfigRegistry->getProcessConfigurations();
        \usort($processConfigurations, [$this, 'processSorter']);

        $publicCount = \array_reduce($processConfigurations, [$this, 'publicProcessCounter'], 0);
        $privateCount = \array_reduce($processConfigurations, [$this, 'privateProcessCounter'], 0);
        $output->writeln("<info>There are {$publicCount} process configurations defined (and {$privateCount} private) :</info>");

        $messages = [];
        foreach ($processConfigurations as $processConfiguration) {
            if ($processConfiguration->isPublic() || $input->getOption('all')) {
                $countTasks = \count($processConfiguration->getTaskConfigurations());
                $message = "<info> - </info>{$processConfiguration->getCode()}<info> with {$countTasks} tasks</info>";

                if ($processConfiguration->isPrivate()) {
                    $message .= " <comment>(private)</comment>";
                }

                $messages[] = [
                    'process' => $processConfiguration,
                    'output'  => $message,
                ];
            }
        }

        // Add process descriptions at a fixed position
        $maxMessageLength = \array_reduce($messages, [$this, 'maxMessageLengthFilter'], 0);
        $outputMessages = [];
        foreach ($messages as $message) {
            /** @var ProcessConfiguration $processConfiguration */
            $processConfiguration = $message['process'];
            $outputMessage = $message['output'];

            if ($processConfiguration->getDescription()) {
                $outputMessage = $this->padMessage($outputMessage, $maxMessageLength + 3);
                $outputMessage .= "{$processConfiguration->getDescription()}";
            }

            $outputMessages[] = $outputMessage;
        }

        // Output messages
        foreach ($outputMessages as $message) {
            $output->writeln($message);
        }
    }

    /**
     * Counter callback for public processes
     *
     * @param int                  $sum
     * @param ProcessConfiguration $processConfiguration
     *
     * @return int
     */
    public function publicProcessCounter($sum, ProcessConfiguration $processConfiguration)
    {
        return $sum + ($processConfiguration->isPublic() ? 1 : 0);
    }

    /**
     * Counter callback for private processes
     *
     * @param int                  $sum
     * @param ProcessConfiguration $processConfiguration
     *
     * @return int
     */
    public function privateProcessCounter($sum, ProcessConfiguration $processConfiguration)
    {
        return $sum + ($processConfiguration->isPrivate() ? 1 : 0);
    }

    /**
     * Sorter callback for process codes
     *
     * @param ProcessConfiguration $a
     * @param ProcessConfiguration $b
     *
     * @return int
     */
    public function processSorter(ProcessConfiguration $a, ProcessConfiguration $b)
    {
        if ($a->getCode() === $b->getCode()) {
            return 0;
        }

        return ($a->getCode() < $b->getCode()) ? -1 : 1;
    }

    /**
     * Filter callback to find max message length
     *
     * @param ProcessConfiguration $a
     * @param ProcessConfiguration $b
     *
     * @return int
     */
    public function maxMessageLengthFilter($max, array $message)
    {
        return \max($max, strlen($this->filterOutTags($message['output'])));
    }

    /**
     * Returns a padded message (without counting metadata)
     *
     * @param string $message
     * @param int    $length
     *
     * @return string
     */
    protected function padMessage($message, $length = 80)
    {
        $currentLen = strlen($this->filterOutTags($message));
        if ($currentLen < $length) {
            $message .= str_repeat(' ', $length - $currentLen);
        }

        return $message;
    }

    /**
     * Filter out tags used in console outputs
     *
     * @param $string
     *
     * @return string
     */
    protected function filterOutTags($string)
    {
        return preg_replace('/<[^<>]*>/', '', $string);
    }
}
