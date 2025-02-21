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

namespace CleverAge\ProcessBundle\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Allow the task to configure it's options, set default basic options for errors handling.
 */
abstract class AbstractConfigurableTask implements InitializableTaskInterface, ResetInterface
{
    protected ?array $options = null;

    /**
     * Only validate the options at initialization, ensuring that the task will not fail at runtime.
     */
    public function initialize(ProcessState $state): void
    {
        $this->getOptions($state);
    }

    public function reset(): void
    {
        $this->options = null;
    }

    protected function getOptions(ProcessState $state): ?array
    {
        if (null === $this->options) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);
            $this->options = $resolver->resolve($state->getContextualizedOptions());
        }

        return $this->options;
    }

    protected function getOption(ProcessState $state, string $code): mixed
    {
        $options = $this->getOptions($state);
        if (!\array_key_exists($code, $options)) {
            throw new \InvalidArgumentException("Missing option {$code}");
        }

        return $options[$code];
    }

    abstract protected function configureOptions(OptionsResolver $resolver): void;
}
