<?php declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DeserializerTask extends AbstractConfigurableTask
{
    /** @var SerializerInterface */
    protected $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     */
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

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'type',
                'format',
            ]
        );
        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedTypes('format', ['string']);
        $resolver->setDefaults(
            [
                'context' => [],
            ]
        );
    }
}
