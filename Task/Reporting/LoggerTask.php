<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Reporting;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;

/**
 * Class LoggerTask
 *
 * Add custom log in state
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class LoggerTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     *
     * @internal param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        PropertyAccessorInterface $accessor
    ) {
        $this->logger = $logger;
        $this->accessor = $accessor;
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws CircularReferenceException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $context = [];
        foreach ($options['context'] as $contextInfo) {
            $context[$contextInfo] = $this->accessor->getValue($state, $contextInfo);
        }
        if ($options['reference']) {
            $context['reference'] = $options['reference'];
        }
        $this->logger->log($options['level'], $options['message'], $context);

        $state->setOutput($state->getInput());
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'level' => 'debug',
                'message' => 'Log state input',
                'context' => ['input'],
                'reference' => null,
            ]
        );
        $resolver->setAllowedTypes('level', ['string']);
        $resolver->setAllowedTypes('message', ['string']);
        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setAllowedTypes('reference', ['string', 'null']);
    }
}
