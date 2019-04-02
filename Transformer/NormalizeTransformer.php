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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize the given value based on options
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class NormalizeTransformer implements ConfigurableTransformerInterface
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * @param NormalizerInterface $normalizer
     */
    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
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
     * @return array|bool|float|int|mixed|string
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function transform($value, array $options = [])
    {
        return $this->normalizer->normalize(
            $value,
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
        return 'normalize';
    }
}
