<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Validation;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Sidus\BaseBundle\Validator\Mapping\Loader\BaseLoader;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Options;
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
    /** @var LoggerInterface */
    protected $logger;

    /** @var ValidatorInterface */
    protected $validator;

    /**
     * @param LoggerInterface    $logger
     * @param ValidatorInterface $validator
     */
    public function __construct(LoggerInterface $logger, ValidatorInterface $validator)
    {
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $violations = $this->validator->validate(
            $state->getInput(),
            $this->getOption($state, 'constraints'),
            $options['groups']
        );

        if (0 < $violations->count()) {
            /** @var  $violation ConstraintViolationInterface */
            foreach ($violations as $violation) {
                $invalidValue = $violation->getInvalidValue();

                $logContext = [
                    'property' => $violation->getPropertyPath(),
                    'violation_code' => $violation->getCode(),
                    'invalid_value' => $invalidValue,
                ];
                if ($this->getOption($state, 'log_errors')) {
                    $this->logger->warning($violation->getMessage(), $logContext);
                }
            }

            throw new \UnexpectedValueException("{$violations->count()} constraint violations detected on validation");
        }

        $state->setOutput($state->getInput());
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('log_errors', true);
        $resolver->addAllowedTypes('log_errors', ['bool']);

        $resolver->setDefault('groups', null);
        $resolver->addAllowedTypes('groups', ['NULL', 'array']);

        $resolver->setDefault('constraints', null);
        $resolver->addAllowedTypes('constraints', ['NULL', 'array']);
        $resolver->setNormalizer(
            'constraints',
            static function (Options $options, $constraints) {
                if (null === $constraints) {
                    return null;
                }

                return (new BaseLoader())->loadCustomConstraints($constraints);
            }
        );
    }
}
