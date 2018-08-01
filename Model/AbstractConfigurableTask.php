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

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allow the task to configure it's options, set default basic options for errors handling
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
abstract class AbstractConfigurableTask implements InitializableTaskInterface
{
    // These constants are deprecated
    public const LOG_ERRORS = 'log_errors';
    public const ERROR_STRATEGY = 'error_strategy';

    public const STRATEGY_SKIP = 'skip';
    public const STRATEGY_STOP = 'stop';
    public const STRATEGY_CONTINUE = 'continue';

    private const DEPRECATED_MSG_PATTERN = 'The %%option%% option is deprecated since version 1.2 and will be removed in 2.0. Use the %%option%% task property instead.';

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
                self::ERROR_STRATEGY => null, // self::STRATEGY_SKIP
                self::LOG_ERRORS => null, // true
            ]
        );
        $resolver->setAllowedValues(
            self::ERROR_STRATEGY,
            [
                null,
                self::STRATEGY_STOP,
                self::STRATEGY_SKIP,
                self::STRATEGY_CONTINUE,
            ]
        );

        $resolver->setAllowedTypes(self::LOG_ERRORS, ['boolean', 'null']);
        $resolver->setNormalizer(
            self::ERROR_STRATEGY,
            function (Options $options, $value) {
                if (null !== $value) {
                    @trigger_error(
                        strtr(self::DEPRECATED_MSG_PATTERN, ['%%option%%' => self::ERROR_STRATEGY]),
                        E_USER_DEPRECATED
                    );

                    return $value;
                }

                return self::STRATEGY_SKIP;
            }
        );
        $resolver->setNormalizer(
            self::LOG_ERRORS,
            function (Options $options, $value) {
                if (null !== $value) {
                    @trigger_error(
                        strtr(self::DEPRECATED_MSG_PATTERN, ['%%option%%' => self::LOG_ERRORS]),
                        E_USER_DEPRECATED
                    );

                    return $value;
                }

                return true;
            }
        );
    }
}
