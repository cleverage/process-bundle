<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
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
    /** @var LoggerInterface */
    protected $logger;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(LoggerInterface $logger, PropertyAccessorInterface $accessor)
    {
        $this->logger = $logger;
        $this->accessor = $accessor;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Exception
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
                $state->addErrorContextValue('property', $key);
                $state->addErrorContextValue('value', $value);
                $state->setException($e);

                return;
            }
        }

        $state->setOutput($input);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'values',
            ]
        );
        $resolver->setAllowedTypes('values', ['array']);
    }
}
