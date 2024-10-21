<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Serialization;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize input to output with configurable class and format.
 */
class DenormalizerTask extends AbstractConfigurableTask
{
    public function __construct(
        protected DenormalizerInterface $denormalizer,
    ) {
    }

    public function execute(ProcessState $state): void
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

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['class']);
        $resolver->setAllowedTypes('class', ['string']);
        $resolver->setDefaults([
            'format' => null,
            'context' => [],
        ]);
        $resolver->setAllowedTypes('format', ['null', 'string']);
        $resolver->setAllowedTypes('context', ['array']);
    }
}
