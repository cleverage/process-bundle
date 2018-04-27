<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Reporting;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Count the time between 2 iterations
 */
class AdvancedStatCounterTask extends AbstractConfigurableTask
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \DateTime */
    protected $startedAt;

    /** @var \DateTime */
    protected $lastUpdate;

    /** @var int */
    protected $counter = 0;

    /** @var int */
    protected $preInitCounter = 0;

    /**
     * AdvancedStatCounterTask constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $now = new \DateTime();
        if (!$this->startedAt) {
            $this->startedAt = $now;
        }
        if ($this->preInitCounter < $this->getOption($state, 'skip_first')) {
            $this->preInitCounter++;

            return;
        }
        if ($this->lastUpdate && 0 === $this->counter % $this->getOption($state, 'show_every')) {
            $diff = $now->diff($this->lastUpdate);
            $fullText = "Last iteration {$diff->format('%H:%I:%S')} ago";
            $items = $this->getOption($state, 'num_items') * $this->counter;
            $seconds = $now->getTimestamp() - $this->startedAt->getTimestamp();
            $rate = 'n/a';
            if ($seconds > 0) {
                $rate = number_format($items / $seconds, 2, ',', ' ');
            }
            $fullText .= " - {$rate} items/s - {$items} items processed";
            $fullText .= " in {$now->diff($this->startedAt)->format('%H:%I:%S')}";

            $this->logger->info($fullText, $state->getLogContext());
            $consoleOutput = $state->getConsoleOutput();
            if ($consoleOutput) {
                $consoleOutput->writeln("<info>### {$fullText}</info>");
            }
        }
        $this->counter++;
        $this->lastUpdate = $now;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'num_items' => 1,
                'skip_first' => 0,
                'show_every' => 1,
            ]
        );
        $resolver->setAllowedTypes('num_items', ['int']);
        $resolver->setAllowedTypes('skip_first', ['int']);
        $resolver->setAllowedTypes('show_every', ['int']);
    }
}
