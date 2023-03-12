<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Serialization;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use UnexpectedValueException;

/**
 * Normalize input to output with configurable format
 */
class NormalizerTask extends AbstractConfigurableTask
{
    public function __construct(
        protected NormalizerInterface $normalizer
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);

        if (! $this->normalizer->supportsNormalization($state->getInput(), $options['format'])) {
            throw new UnexpectedValueException('Given value is not normalizable for format ' . $options['format']);
        }

        $normalizedData = $this->normalizer->normalize(
            $state->getInput(),
            $options['format'],
            $options['context']
        );
        $state->setOutput($normalizedData);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['format']);
        $resolver->setAllowedTypes('format', ['string']);
        $resolver->setDefaults([
            'context' => [],
        ]);
    }
}
