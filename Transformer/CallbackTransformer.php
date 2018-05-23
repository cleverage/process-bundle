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

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Convert input based on a callback
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CallbackTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $parameters = $options['additional_parameters'];
        array_unshift($parameters, $value);

        return \call_user_func_array($options['callback'], $parameters);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'callback';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'callback',
            ]
        );
        $resolver->setAllowedTypes('callback', ['string', 'array']);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'callback',
            function (Options $options, $value) {
                if (!\is_callable($value)) {
                    throw new InvalidOptionsException(
                        'Callback option must be callable'
                    );
                }

                return $value;
            }
        );
        $resolver->setDefaults([
            'additional_parameters' => [],
        ]);
        $resolver->setAllowedTypes('additional_parameters', ['array']);
    }
}
