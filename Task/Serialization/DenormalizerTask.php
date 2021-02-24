<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Serialization;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize input to output with configurable class and format
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DenormalizerTask extends AbstractConfigurableTask
{
    /** @var DenormalizerInterface */
    protected $denormalizer;

    /**
     * @param DenormalizerInterface $denormalizer
     */
    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * @param ProcessState $state
     *
     * @throws UnexpectedValueException
     * @throws RuntimeException
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws ExtraAttributesException
     * @throws BadMethodCallException
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $normalizedData = $this->denormalizer->denormalize(
            $state->getInput(),
            $options['class'],
            $options['format'],
            $options['context']
        );
        $state->setOutput($normalizedData);
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
                'class',
            ]
        );
        $resolver->setAllowedTypes('class', ['string']);
        $resolver->setDefaults(
            [
                'format' => null,
                'context' => [],
            ]
        );
        $resolver->setAllowedTypes('format', ['null', 'string']);
        $resolver->setAllowedTypes('context', ['array']);
    }
}
