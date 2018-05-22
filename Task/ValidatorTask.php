<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validate the input and pass it to the output
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ValidatorTask extends AbstractConfigurableTask
{
    /** @var ValidatorInterface */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $violations = $this->validator->validate($state->getInput(), null, $options['groups']);

        if ($options[self::LOG_ERRORS]) {
            /** @var  $violation ConstraintViolationInterface */
            foreach ($violations as $violation) {
                $invalidValue = $violation->getInvalidValue();
                $state->log(
                    $violation->getMessage(),
                    LogLevel::ERROR,
                    $violation->getPropertyPath(),
                    [
                        'code' => $violation->getCode(),
                        'invalid_value' => \is_object($invalidValue) ? \get_class($invalidValue) : $invalidValue,
                    ]
                );
            }
        }

        if (0 < $violations->count()) {
            $state->setError($state->getInput());
            if ($options[self::ERROR_STRATEGY] === self::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($options[self::ERROR_STRATEGY] === self::STRATEGY_STOP) {
                $state->stop(
                    new \UnexpectedValueException("{$violations->count()} constraint violations detected on validation")
                );
            }
        }

        $state->setOutput($state->getInput());
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('groups', null);
        $resolver->addAllowedTypes('groups', ['NULL', 'array']);
    }
}
