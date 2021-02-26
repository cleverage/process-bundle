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
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize input to output with configurable format
 * 
 * ##### Task reference
 * 
 *  * **Service**: `CleverAge\ProcessBundle\Task\Serialization\NormalizerTask`
 *  * **Input**: `object`, any normalizable object
 *  * **Output**: `array`, a normalized value as an array
 * 
 * ##### Options
 *
 * * `format` (`string`, _required_): Format for normalization ("json", "xml", ... an empty string should also work)
 * * `context` (`array`, _defaults to_ `[]`): Will be passed directly to the third parameter of the normalize method
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class NormalizerTask extends AbstractConfigurableTask
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * @internal
     */
    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);

        if (!$this->normalizer->supportsNormalization($state->getInput(), $options['format'])) {
            throw new \UnexpectedValueException('Given value is not normalizable for format '.$options['format']);
        }

        $normalizedData = $this->normalizer->normalize(
            $state->getInput(),
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
                'format',
            ]
        );
        $resolver->setAllowedTypes('format', ['string']);
        $resolver->setDefaults(
            [
                'context' => [],
            ]
        );
    }
}
