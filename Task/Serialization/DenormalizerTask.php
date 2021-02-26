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
 * ##### Task reference
 * 
 * * **Service**: `CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask`
 * * **Input**: `array`
 * * **Output**: `object`, instance of `class`, as a product of the denormalization
 * 
 * ##### Options
 *
 * * `class` (`string`, _required_): Destination class for denormalization
 * * `format` (`string`, _defaults to_ `null`): Format for denormalization ("json", "xml", ... an empty string should also work)
 * * `context` (`array`, _defaults to_ `[]`): Will be passed directly to the 4th parameter of the denormalize method
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DenormalizerTask extends AbstractConfigurableTask
{
    /** @var DenormalizerInterface */
    protected $denormalizer;

    /**
     * @internal
     */
    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
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
     * {@inheritDoc}
     *
     * @internal
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
