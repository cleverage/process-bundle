<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
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
 * Count the time between 2 iterations.
 */
class AdvancedStatCounterTask extends AbstractConfigurableTask
{
    protected ?\DateTime $startedAt = null;

    protected ?\DateTime $lastUpdate = null;

    protected int $counter = 0;

    protected int $preInitCounter = 0;

    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $now = new \DateTime();
        if (!$this->startedAt instanceof \DateTime) {
            $this->startedAt = $now;
            $this->lastUpdate = $now;
        }
        if ($this->preInitCounter < $this->getOption($state, 'skip_first')) {
            ++$this->preInitCounter;
            $state->setSkipped(true);

            return;
        }
        if ($this->counter > 0 && 0 === $this->counter % $this->getOption($state, 'show_every')) {
            $diff = $now->diff($this->lastUpdate);
            $fullText = "Last iteration {$diff->format('%H:%I:%S')} ago";
            $items = $this->getOption($state, 'num_items') * $this->counter;
            $seconds = $now->getTimestamp() - $this->startedAt->getTimestamp();
            $rate = 'n/a';
            if ($seconds > 0) {
                $rate = number_format($items / $seconds, 2, ',', ' ');
            }
            $fullText .= " - {$rate} items/s - {$items} items processed";
            $fullText .= " in {$now->diff($this->startedAt)
                ->format('%H:%I:%S')}";

            $this->lastUpdate = $now;
            $this->logger->info($fullText);
        } else {
            $state->setSkipped(true);
        }
        ++$this->counter;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'num_items' => 1,
            'skip_first' => 0,
            'show_every' => 1,
        ]);
        $resolver->setAllowedTypes('num_items', ['int']);
        $resolver->setAllowedTypes('skip_first', ['int']);
        $resolver->setAllowedTypes('show_every', ['int']);
    }
}
