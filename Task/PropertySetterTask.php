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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Accepts an object or an array as input and sets values from configuration
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class PropertySetterTask extends AbstractConfigurableTask
{
    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(LoggerInterface $logger, PropertyAccessorInterface $accessor)
    {
        parent::__construct($logger);
        $this->accessor = $accessor;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $input = $state->getInput();
        /** @noinspection ForeachSourceInspection */
        foreach ($options['values'] as $key => $value) {
            try {
                $this->accessor->setValue($input, $key, $value);
            } catch (\Exception $e) {
                $state->setError($input);
                $logContext = $state->getLogContext();
                $logContext['property'] = $key;
                $logContext['value'] = $value;
                $this->logger->error($e->getMessage(), $logContext);
                if ($options[self::ERROR_STRATEGY] === self::STRATEGY_SKIP) {
                    $state->setSkipped(true);
                } elseif ($options[self::ERROR_STRATEGY] === self::STRATEGY_STOP) {
                    $state->stop($e);
                }
            }
        }

        $state->setOutput($input);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(
            [
                'values',
            ]
        );
        $resolver->setAllowedTypes('values', ['array']);
    }
}
