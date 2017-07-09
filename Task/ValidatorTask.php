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

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Psr\Log\LogLevel;
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
        $violations = $this->validator->validate($state->getInput());
        $options = $this->getOptions($state);

        if ($options[AbstractConfigurableTask::LOG_ERRORS]) {
            /** @var  $violation ConstraintViolationInterface */
            foreach ($violations as $violation) {
                $invalidValue = $violation->getInvalidValue();
                $state->log(
                    $violation->getMessage(),
                    LogLevel::ERROR,
                    $violation->getPropertyPath(),
                    [
                        'code' => $violation->getCode(),
                        'invalid_value' => is_object($invalidValue) ? get_class($invalidValue) : $invalidValue,
                    ]
                );
            }
        }

        if (0 < $violations->count()) {
            $state->setError($state->getInput());
            if ($options[AbstractConfigurableTask::SKIP_ON_ERROR]) {
                $state->setSkipped(true);
            } elseif ($options[AbstractConfigurableTask::STOP_ON_ERROR]) {
                $state->stop(
                    new \UnexpectedValueException("{$violations->count()} constraint violations detected on validation")
                );
            }
        }

        $state->setOutput($state->getInput());
    }
}
