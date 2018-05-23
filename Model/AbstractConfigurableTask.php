<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allow the task to configure it's options, set default basic options for errors handling
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
abstract class AbstractConfigurableTask implements InitializableTaskInterface
{
    public const LOG_ERRORS = 'log_errors';
    public const ERROR_STRATEGY = 'error_strategy';

    public const STRATEGY_SKIP = 'skip';
    public const STRATEGY_STOP = 'stop';
    public const STRATEGY_CONTINUE = 'continue';

    /** @var array */
    protected $options;

    /**
     * Only validate the options at initialization, ensuring that the task will not fail at runtime
     *
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function initialize(ProcessState $state)
    {
        $this->getOptions($state);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return array
     */
    protected function getOptions(ProcessState $state)
    {
        if (null === $this->options) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);
            $this->options = $resolver->resolve($state->getContextualizedOptions());
        }

        return $this->options;
    }

    /**
     * @param ProcessState $state
     * @param string       $code
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return mixed
     */
    protected function getOption(ProcessState $state, $code)
    {
        $options = $this->getOptions($state);
        if (!array_key_exists($code, $options)) {
            throw new \InvalidArgumentException("Missing option {$code}");
        }

        return $options[$code];
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
                self::ERROR_STRATEGY => self::STRATEGY_SKIP,
                self::LOG_ERRORS => true,
            ]
        );
        $resolver->setAllowedValues(
            self::ERROR_STRATEGY,
            [
                self::STRATEGY_STOP,
                self::STRATEGY_SKIP,
                self::STRATEGY_CONTINUE,
            ]
        );
        $resolver->setAllowedTypes(self::LOG_ERRORS, ['boolean']);
    }
}
