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
use Symfony\Component\Serializer\SerializerInterface;

class DeserializerTask extends AbstractConfigurableTask
{
    public function __construct(
        protected SerializerInterface $serializer
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $serializeData = $this->serializer->deserialize(
            $state->getInput(),
            $options['type'],
            $options['format'],
            $options['context']
        );
        $state->setOutput($serializeData);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['type', 'format']);
        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedTypes('format', ['string']);
        $resolver->setDefaults([
            'context' => [],
        ]);
    }
}
