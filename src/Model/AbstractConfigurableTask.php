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

namespace CleverAge\ProcessBundle\Model;

use InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allow the task to configure it's options, set default basic options for errors handling
 */
abstract class AbstractConfigurableTask implements InitializableTaskInterface
{
    protected ?array $options = null;

    /**
     * Only validate the options at initialization, ensuring that the task will not fail at runtime
     */
    public function initialize(ProcessState $state): void
    {
        $this->getOptions($state);
    }

    protected function getOptions(ProcessState $state): ?array
    {
        if ($this->options === null) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);
            $this->options = $resolver->resolve($state->getContextualizedOptions());
        }

        return $this->options;
    }

    protected function getOption(ProcessState $state, string $code): mixed
    {
        $options = $this->getOptions($state);
        if (! array_key_exists($code, $options)) {
            throw new InvalidArgumentException("Missing option $code");
        }

        return $options[$code];
    }

    abstract protected function configureOptions(OptionsResolver $resolver): void;
}
