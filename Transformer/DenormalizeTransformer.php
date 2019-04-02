<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize the given value based on options
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DenormalizeTransformer implements ConfigurableTransformerInterface
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
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
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
        $resolver->setAllowedTypes('format', ['NULL', 'string']);
        $resolver->setAllowedTypes('context', ['array']);
    }

    /**
     * @param mixed $value
     * @param array $options
     *
     * @return mixed|object
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function transform($value, array $options = [])
    {
        return $this->denormalizer->denormalize(
            $value,
            $options['class'],
            $options['format'],
            $options['context']
        );
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'denormalize';
    }
}
