<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
    const LOG_ERRORS = 'log_errors';
    const ERROR_STRATEGY = 'error_strategy';

    const STRATEGY_SKIP = 'skip';
    const STRATEGY_STOP = 'stop';
    const STRATEGY_CONTINUE = 'continue';


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
            $this->options = $resolver->resolve($state->getTaskConfiguration()->getOptions());
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
        $resolver->setAllowedTypes(self::LOG_ERRORS, ['bool']);
    }
}
